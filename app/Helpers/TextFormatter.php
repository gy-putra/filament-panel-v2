<?php

if (!function_exists('auto_markdown')) {
    /**
     * Automatically format plain text into Markdown-like HTML
     * 
     * This function analyzes raw text and converts it into structured, readable HTML:
     * - Detects uppercase section headers and converts them to heading tags
     * - Splits content by punctuation, line breaks, or vertical bars into lists
     * - Bolds text before colons (e.g., "Hotel:" becomes "<strong>Hotel:</strong>")
     * - Adds proper paragraph spacing and formatting
     * - Sanitizes output for safety
     * 
     * @param string|null $text The raw text to format
     * @return string Formatted HTML output
     */
    function auto_markdown(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Clean and normalize the input
        $text = trim($text);
        
        // Check if the content is already HTML (from RichEditor)
        if (strip_tags($text) !== $text) {
            // Content contains HTML tags, return as-is but with some cleanup
            // Remove any potential script tags for security
            $text = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $text);
            
            // Ensure proper spacing and formatting for existing HTML
            $text = preg_replace('/>\s+</', '><', $text); // Remove extra spaces between tags
            $text = str_replace(['<p></p>', '<p> </p>'], '', $text); // Remove empty paragraphs
            
            return $text;
        }
        
        // Original plain text processing logic
        $text = preg_replace('/\r\n|\r/', "\n", $text); // Normalize line endings
        
        // Split text into lines for processing
        $lines = explode("\n", $text);
        $formatted = [];
        $currentList = [];
        $inList = false;

        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip empty lines but preserve them for paragraph breaks
            if (empty($line)) {
                // Close any open list
                if ($inList && !empty($currentList)) {
                    $formatted[] = '<ul class="list-disc list-inside space-y-1 ml-4">';
                    $formatted = array_merge($formatted, $currentList);
                    $formatted[] = '</ul>';
                    $currentList = [];
                    $inList = false;
                }
                $formatted[] = '<br>';
                continue;
            }

            // Check if line is an uppercase section header
            if (preg_match('/^[A-Z\s\-:]+$/', $line) && strlen($line) > 3) {
                // Close any open list first
                if ($inList && !empty($currentList)) {
                    $formatted[] = '<ul class="list-disc list-inside space-y-1 ml-4">';
                    $formatted = array_merge($formatted, $currentList);
                    $formatted[] = '</ul>';
                    $currentList = [];
                    $inList = false;
                }
                
                // Add section header
                $headerText = ucwords(strtolower($line));
                $formatted[] = '<h3 class="text-lg font-bold text-gray-900 mt-6 mb-3">' . htmlspecialchars($headerText) . '</h3>';
                continue;
            }

            // Check if line contains list indicators (|, •, -, or multiple items separated by punctuation)
            $hasListIndicators = (
                strpos($line, '|') !== false || 
                strpos($line, '•') !== false || 
                preg_match('/^\s*[-*]\s/', $line) ||
                (substr_count($line, ',') > 1 && strlen($line) > 50) ||
                (substr_count($line, '.') > 1 && strlen($line) > 50)
            );

            if ($hasListIndicators) {
                // Split by various delimiters
                $items = preg_split('/[|•]|(?<=\w)\s*[-]\s*(?=\w)/', $line);
                
                // If no pipe/bullet splits found, try comma/period splitting for long lines
                if (count($items) <= 1 && strlen($line) > 50) {
                    $items = preg_split('/[,.]\s+/', $line);
                }

                foreach ($items as $item) {
                    $item = trim($item);
                    if (!empty($item)) {
                        // Clean up punctuation at the end
                        $item = rtrim($item, '.,;');
                        
                        if (!empty($item)) {
                            // Format colons (bold text before colon) - escape first, then add HTML
                            $item = htmlspecialchars($item);
                            $item = preg_replace('/^([^:]+):\s*/', '<strong>$1:</strong> ', $item);
                            
                            $currentList[] = '<li class="text-gray-700">' . $item . '</li>';
                            $inList = true;
                        }
                    }
                }
            } else {
                // Close any open list first
                if ($inList && !empty($currentList)) {
                    $formatted[] = '<ul class="list-disc list-inside space-y-1 ml-4">';
                    $formatted = array_merge($formatted, $currentList);
                    $formatted[] = '</ul>';
                    $currentList = [];
                    $inList = false;
                }

                // Regular paragraph text
                // Format colons (bold text before colon) - escape first, then add HTML
                $line = htmlspecialchars($line);
                $line = preg_replace('/^([^:]+):\s*/', '<strong>$1:</strong> ', $line);
                
                // Add as paragraph
                $formatted[] = '<p class="text-gray-700 leading-relaxed mb-3">' . $line . '</p>';
            }
        }

        // Close any remaining open list
        if ($inList && !empty($currentList)) {
            $formatted[] = '<ul class="list-disc list-inside space-y-1 ml-4">';
            $formatted = array_merge($formatted, $currentList);
            $formatted[] = '</ul>';
        }

        // Join all formatted content
        $result = implode("\n", $formatted);
        
        // Clean up multiple consecutive <br> tags
        $result = preg_replace('/(<br>\s*){3,}/', '<br><br>', $result);
        
        // Remove <br> tags that are immediately before or after block elements
        $result = preg_replace('/<br>\s*(<\/?(h[1-6]|ul|li|p)[^>]*>)/', '$1', $result);
        $result = preg_replace('/(<\/?(h[1-6]|ul|li|p)[^>]*>)\s*<br>/', '$1', $result);

        return $result;
    }
}