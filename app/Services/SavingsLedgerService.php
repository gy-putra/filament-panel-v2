<?php

namespace App\Services;

use App\Models\Tabungan;
use App\Models\TabunganSetoran;
use App\Models\TabunganAlokasi;
use App\Models\Invoice;
use App\Events\AllocationPosted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class SavingsLedgerService
{
    /**
     * Approve a deposit and update the available balance
     */
    public function approveDeposit(TabunganSetoran $setoran, ?string $catatan = null): bool
    {
        return DB::transaction(function () use ($setoran, $catatan) {
            // Lock the savings account for update
            $tabungan = Tabungan::lockForUpdate()->findOrFail($setoran->tabungan_id);
            
            // Update deposit status
            $setoran->update([
                'status_verifikasi' => 'approved',
                'verified_by' => auth()->id(),
                'verified_at' => now(),
                'catatan' => $catatan,
            ]);

            // No need to update database columns as balances are calculated dynamically
            // The status change from 'pending' to 'approved' automatically updates the calculations

            Log::info('Deposit approved', [
                'setoran_id' => $setoran->id,
                'tabungan_id' => $tabungan->id,
                'nominal' => $setoran->nominal,
                'approved_by' => auth()->id(),
            ]);

            return true;
        });
    }

    /**
     * Reject a deposit
     */
    public function rejectDeposit(TabunganSetoran $setoran, string $catatan): bool
    {
        $setoran->update([
            'status_verifikasi' => 'rejected',
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            'catatan' => $catatan,
        ]);

        Log::info('Deposit rejected', [
            'setoran_id' => $setoran->id,
            'tabungan_id' => $setoran->tabungan_id,
            'nominal' => $setoran->nominal,
            'rejected_by' => auth()->id(),
            'reason' => $catatan,
        ]);

        return true;
    }

    /**
     * Create a new allocation (draft status)
     * When created (draft): The available balance should decrease, and the locked balance should increase
     */
    public function createAllocation(array $data): TabunganAlokasi
    {
        return DB::transaction(function () use ($data) {
            // Get the savings account to validate balance
            $tabungan = Tabungan::find($data['tabungan_id']);
            if (!$tabungan) {
                throw new Exception('Tabungan not found');
            }

            // Check if sufficient available balance for the allocation
            if ($tabungan->saldo_tersedia < $data['nominal']) {
                throw new Exception('Insufficient available balance for allocation');
            }

            // Create the allocation with draft status
            $alokasi = TabunganAlokasi::create([
                'tabungan_id' => $data['tabungan_id'],
                'pendaftaran_id' => $data['pendaftaran_id'] ?? null,
                'invoice_id' => $data['invoice_id'] ?? null,
                'tanggal' => $data['tanggal'] ?? now(),
                'nominal' => $data['nominal'],
                'status' => 'draft',
                'catatan' => $data['catatan'] ?? null,
            ]);

            Log::info('Allocation created (draft)', [
                'alokasi_id' => $alokasi->id,
                'tabungan_id' => $tabungan->id,
                'nominal' => $alokasi->nominal,
                'created_by' => auth()->id(),
                'available_balance_before' => $tabungan->saldo_tersedia + $data['nominal'], // Before allocation
                'locked_balance_before' => $tabungan->saldo_terkunci - $data['nominal'], // Before allocation
                'available_balance_after' => $tabungan->fresh()->saldo_tersedia,
                'locked_balance_after' => $tabungan->fresh()->saldo_terkunci,
            ]);

            return $alokasi;
        });
    }

    /**
     * Post an allocation (finalize allocation - funds leave savings and go to invoice)
     * When posted: The locked balance should decrease, meaning the reserved funds are officially released and allocated to an invoice
     */
    public function postAllocation(TabunganAlokasi $alokasi): bool
    {
        Log::info('Starting postAllocation', ['alokasi_id' => $alokasi->id]);
        
        if ($alokasi->status !== 'draft') {
            throw new Exception('Only draft allocations can be posted');
        }

        return DB::transaction(function () use ($alokasi) {
            Log::info('Inside transaction', ['alokasi_id' => $alokasi->id]);
            
            try {
                // Get the savings account with a simple find first
                Log::info('About to get tabungan', ['tabungan_id' => $alokasi->tabungan_id]);
                $tabungan = Tabungan::find($alokasi->tabungan_id);
                if (!$tabungan) {
                    throw new Exception('Tabungan not found');
                }
                Log::info('Tabungan found', ['tabungan_id' => $tabungan->id]);
            } catch (\Exception $e) {
                Log::error('Error getting tabungan', [
                    'tabungan_id' => $alokasi->tabungan_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

            // Check if sufficient locked balance (draft allocations)
            if ($tabungan->saldo_terkunci < $alokasi->nominal) {
                throw new Exception('Insufficient locked balance');
            }

            // Store balance information before posting
            $lockedBalanceBefore = $tabungan->saldo_terkunci;
            $availableBalanceBefore = $tabungan->saldo_tersedia;

            // Create or update invoice if allocation doesn't have one
            $invoice = null;
            if (!$alokasi->invoice_id) {
                Log::info('Creating new invoice for allocation', ['alokasi_id' => $alokasi->id]);
                
                $invoice = Invoice::createFromAllocation($alokasi);

                // Link the allocation to the created invoice
                $alokasi->update(['invoice_id' => $invoice->id]);
                
                Log::info('Invoice created and linked', [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->nomor_invoice,
                    'alokasi_id' => $alokasi->id
                ]);
            } else {
                // If invoice already exists, update its total amount
                $invoice = Invoice::find($alokasi->invoice_id);
                if ($invoice) {
                    $invoice->addAllocationAmount($alokasi->nominal);
                    Log::info('Existing invoice updated', [
                        'invoice_id' => $invoice->id,
                        'new_total' => $invoice->fresh()->total_amount
                    ]);
                }
            }

            // Update allocation status - this will automatically adjust the balance calculations
            // When status changes from 'draft' to 'posted':
            // - Locked balance decreases (no longer in draft status)
            // - Available balance remains the same (funds are disbursed, not returned to available)
            Log::info('Updating allocation status', ['alokasi_id' => $alokasi->id]);
            $alokasi->update(['status' => 'posted']);
            Log::info('Allocation status updated', ['alokasi_id' => $alokasi->id]);

            // Dispatch event for cross-module integration
            Log::info('About to dispatch event', ['alokasi_id' => $alokasi->id]);
            try {
                AllocationPosted::dispatch($alokasi);
                Log::info('Event dispatched successfully', ['alokasi_id' => $alokasi->id]);
            } catch (\Exception $e) {
                Log::error('Failed to dispatch AllocationPosted event', [
                    'alokasi_id' => $alokasi->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue execution even if event dispatch fails
            }

            Log::info('Allocation posted', [
                'alokasi_id' => $alokasi->id,
                'tabungan_id' => $tabungan->id,
                'nominal' => $alokasi->nominal,
                'posted_by' => auth()->id(),
                'invoice_id' => $invoice?->id,
                'invoice_number' => $invoice?->nomor_invoice,
                'locked_balance_before' => $lockedBalanceBefore,
                'available_balance_before' => $availableBalanceBefore,
                'locked_balance_after' => $tabungan->fresh()->saldo_terkunci,
                'available_balance_after' => $tabungan->fresh()->saldo_tersedia,
            ]);

            return true;
        });
    }

    /**
     * Reverse an allocation (cancel allocation - funds return to available balance)
     * When reversed: The locked balance should decrease, and the available balance should increase again, restoring the funds to the user's available pool
     */
    public function reverseAllocation(TabunganAlokasi $alokasi): bool
    {
        if ($alokasi->status !== 'posted') {
            throw new Exception('Only posted allocations can be reversed');
        }

        return DB::transaction(function () use ($alokasi) {
            // Get the savings account (using find instead of lockForUpdate to avoid hanging issues)
            $tabungan = Tabungan::find($alokasi->tabungan_id);
            if (!$tabungan) {
                throw new Exception('Tabungan not found');
            }

            // Store balance information before reversing
            $lockedBalanceBefore = $tabungan->saldo_terkunci;
            $availableBalanceBefore = $tabungan->saldo_tersedia;

            // Update allocation status - this will automatically adjust the balance calculations
            // When status changes from 'posted' to 'reversed':
            // - Locked balance remains the same (reversed allocations don't affect locked balance)
            // - Available balance increases (funds are returned to available pool)
            $alokasi->update(['status' => 'reversed']);

            Log::info('Allocation reversed', [
                'alokasi_id' => $alokasi->id,
                'tabungan_id' => $tabungan->id,
                'nominal' => $alokasi->nominal,
                'reversed_by' => auth()->id(),
                'locked_balance_before' => $lockedBalanceBefore,
                'available_balance_before' => $availableBalanceBefore,
                'locked_balance_after' => $tabungan->fresh()->saldo_terkunci,
                'available_balance_after' => $tabungan->fresh()->saldo_tersedia,
            ]);

            return true;
        });
    }

    /**
     * Get balance summary for a savings account
     */
    public function getBalanceSummary(Tabungan $tabungan): array
    {
        return [
            'saldo_tersedia' => $tabungan->saldo_tersedia,
            'saldo_terkunci' => $tabungan->saldo_terkunci,
            'total_saldo' => $tabungan->total_saldo,
            'total_setoran_approved' => $tabungan->total_approved_deposits,
            'total_alokasi_draft' => $tabungan->total_draft_allocations,
            'total_alokasi_posted' => $tabungan->total_posted_allocations,
        ];
    }

    /**
     * Validate balance consistency
     */
    public function validateBalanceConsistency(Tabungan $tabungan): bool
    {
        $summary = $this->getBalanceSummary($tabungan);
        
        $expectedTotal = $summary['total_setoran_approved'];
        $actualTotal = $summary['saldo_tersedia'] + $summary['saldo_terkunci'];
        
        if (abs($expectedTotal - $actualTotal) > 0.01) { // Allow for small rounding differences
            Log::error('Balance inconsistency detected', [
                'tabungan_id' => $tabungan->id,
                'expected_total' => $expectedTotal,
                'actual_total' => $actualTotal,
                'difference' => $expectedTotal - $actualTotal,
            ]);
            
            return false;
        }
        
        return true;
    }

    /**
     * Get savings account statistics
     */
    public function getAccountStatistics(Tabungan $tabungan): array
    {
        return [
            'total_deposits' => $tabungan->setoran()->count(),
            'approved_deposits' => $tabungan->setoran()->approved()->count(),
            'pending_deposits' => $tabungan->setoran()->pending()->count(),
            'rejected_deposits' => $tabungan->setoran()->rejected()->count(),
            'total_allocations' => $tabungan->alokasi()->count(),
            'posted_allocations' => $tabungan->alokasi()->posted()->count(),
            'draft_allocations' => $tabungan->alokasi()->draft()->count(),
            'reversed_allocations' => $tabungan->alokasi()->reversed()->count(),
            'account_age_days' => $tabungan->created_at->diffInDays(now()),
            'last_deposit_date' => $tabungan->setoran()->latest('tanggal')->first()?->tanggal,
            'last_allocation_date' => $tabungan->alokasi()->latest('tanggal')->first()?->tanggal,
        ];
    }
}