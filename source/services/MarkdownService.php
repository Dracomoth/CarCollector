<?php
/**
 * Markdown Service
 * 
 * Lightweight, safe parser for converting CommonMark-flavored markdown
 * into clean, responsive HTML for model notes and descriptions.
 */

class MarkdownService {
    /**
     * Parse markdown text into sanitized HTML.
     * 
     * @param string|null $markdown
     * @return string
     */
    public static function parse(?string $markdown): string {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        // Normalize line endings
        $text = str_replace(["\r\n", "\r"], "\n", $markdown);

        // Escape raw HTML entities to prevent XSS
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Extract and protect fenced code blocks ``` ... ```
        $codeBlocks = [];
        $text = preg_replace_callback('/```(?:[a-zA-Z0-9_\-]+)?\n(.*?)```/s', function($matches) use (&$codeBlocks) {
            $idx = count($codeBlocks);
            $codeBlocks[$idx] = '<pre class="markdown-pre"><code>' . trim($matches[1]) . '</code></pre>';
            return "%%CODEBLOCK_{$idx}%%";
        }, $text);

        // Extract and protect inline code ` ... `
        $inlineCodes = [];
        $text = preg_replace_callback('/`([^`]+)`/', function($matches) use (&$inlineCodes) {
            $idx = count($inlineCodes);
            $inlineCodes[$idx] = '<code class="markdown-code">' . $matches[1] . '</code>';
            return "%%INLINECODE_{$idx}%%";
        }, $text);

        // Headers: # H1 to ###### H6
        $text = preg_replace('/^######[ \t]+(.*)$/m', '<h6 class="markdown-h6">$1</h6>', $text);
        $text = preg_replace('/^#####[ \t]+(.*)$/m', '<h5 class="markdown-h5">$1</h5>', $text);
        $text = preg_replace('/^####[ \t]+(.*)$/m', '<h4 class="markdown-h4">$1</h4>', $text);
        $text = preg_replace('/^###[ \t]+(.*)$/m', '<h3 class="markdown-h3">$1</h3>', $text);
        $text = preg_replace('/^##[ \t]+(.*)$/m', '<h2 class="markdown-h2">$1</h2>', $text);
        $text = preg_replace('/^#[ \t]+(.*)$/m', '<h1 class="markdown-h1">$1</h1>', $text);

        // Horizontal Rules: --- or ***
        $text = preg_replace('/^[ \t]*(\*|-){3,}[ \t]*$/m', '<hr class="markdown-hr">', $text);

        // Blockquotes: > quote
        $text = preg_replace('/^[ \t]*&gt;[ \t]+(.*)$/m', '<blockquote class="markdown-quote">$1</blockquote>', $text);

        // Bold & Italic combinations: ***bold-italic*** or ___bold-italic___
        $text = preg_replace('/(\*\*\*|___)(.*?)\1/', '<strong><em>$2</em></strong>', $text);

        // Bold: **bold** or __bold__
        $text = preg_replace('/(\*\*|__)(.*?)\1/', '<strong>$2</strong>', $text);

        // Italic: *italic* or _italic_
        $text = preg_replace('/(\*|_)(.*?)\1/', '<em>$2</em>', $text);

        // Strikethrough: ~~del~~
        $text = preg_replace('/~~(.*?)~~/', '<del>$1</del>', $text);

        // Links: [label](url)
        $text = preg_replace_callback('/\[([^\]]+)\]\((https?:\/\/[^\s\)\"\']+)\)/', function($matches) {
            $label = $matches[1];
            $url = $matches[2];
            return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="markdown-link">' . $label . '</a>';
        }, $text);

        // Process lists (unordered and ordered)
        $lines = explode("\n", $text);
        $output = [];
        $inUl = false;
        $inOl = false;

        foreach ($lines as $line) {
            // Unordered list item: * or -
            if (preg_match('/^[ \t]*[\*\-][ \t]+(.*)$/', $line, $m)) {
                if ($inOl) {
                    $output[] = '</ol>';
                    $inOl = false;
                }
                if (!$inUl) {
                    $output[] = '<ul class="markdown-ul">';
                    $inUl = true;
                }
                $output[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Ordered list item: 1.
            if (preg_match('/^[ \t]*\d+\.[ \t]+(.*)$/', $line, $m)) {
                if ($inUl) {
                    $output[] = '</ul>';
                    $inUl = false;
                }
                if (!$inOl) {
                    $output[] = '<ol class="markdown-ol">';
                    $inOl = true;
                }
                $output[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Not a list item: close any open list
            if ($inUl) {
                $output[] = '</ul>';
                $inUl = false;
            }
            if ($inOl) {
                $output[] = '</ol>';
                $inOl = false;
            }

            $output[] = $line;
        }

        if ($inUl) $output[] = '</ul>';
        if ($inOl) $output[] = '</ol>';

        $text = implode("\n", $output);

        // Group regular text blocks into paragraphs
        $blocks = preg_split('/\n{2,}/', trim($text));
        $finalBlocks = [];

        foreach ($blocks as $block) {
            $trimmed = trim($block);
            if ($trimmed === '') continue;

            // If block starts with block-level HTML tags, do not wrap in <p>
            if (preg_match('/^<(h[1-6]|ul|ol|blockquote|hr|div|pre)/i', $trimmed) || preg_match('/^%%CODEBLOCK_\d+%%$/', $trimmed)) {
                $finalBlocks[] = $trimmed;
            } else {
                // Replace single line breaks with <br>
                $pContent = nl2br($trimmed, false);
                $finalBlocks[] = '<p class="markdown-p">' . $pContent . '</p>';
            }
        }

        $result = implode("\n", $finalBlocks);

        // Restore inline code
        foreach ($inlineCodes as $idx => $codeHtml) {
            $result = str_replace("%%INLINECODE_{$idx}%%", $codeHtml, $result);
        }

        // Restore code blocks
        foreach ($codeBlocks as $idx => $blockHtml) {
            $result = str_replace("%%CODEBLOCK_{$idx}%%", $blockHtml, $result);
        }

        return $result;
    }
}
