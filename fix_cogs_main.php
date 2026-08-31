<?php
$file = 'D:/xmapp/htdocs/DataPOS/docs/AI_AGENT_SHOP_OWNER_MANAGER_E2E_AUDIT_PROMPT_MM.md';
$content = file_get_contents($file);
$original = $content;

// Line 211: COGS before return - formula gives 225,000 not 189,000
$content = str_replace(
    '- COGS = `189,000 MMK` (`2×60,000 + 3×3,000 + 1×60,000 + 2×18,000`)',
    '- COGS = `225,000 MMK` (`2×60,000 + 3×3,000 + 1×60,000 + 2×18,000`)',
    $content
);

// Line 212: Gross profit before return
$content = str_replace(
    '- Gross profit = `116,000 MMK`',
    '- Gross profit = `80,000 MMK`',
    $content
);

// Line 225: Net COGS after return
$content = str_replace(
    "- Net COGS = `171,000 MMK`",
    "- Net COGS = `207,000 MMK`",
    $content
);

// Line 226: Gross profit after return
$content = str_replace(
    "- Gross profit = `109,000 MMK`",
    "- Gross profit = `73,000 MMK`",
    $content
);

// Lines 280-286: P&L text block
$content = str_replace(
    "Net COGS          = 171,000 MMK\nGross Profit      = 109,000 MMK\nOperating Expense =  20,000 MMK\nNet Profit        =  89,000 MMK",
    "Net COGS          = 207,000 MMK\nGross Profit      =  73,000 MMK\nOperating Expense =  20,000 MMK\nNet Profit        =  53,000 MMK",
    $content
);

// Lines 315-318: Reconciliation table
$content = str_replace(
    "| COGS | 171,000 | | | |",
    "| COGS | 207,000 | | | |",
    $content
);

$content = str_replace(
    "| Gross Profit | 109,000 | | | |",
    "| Gross Profit | 73,000 | | | |",
    $content
);

$content = str_replace(
    "| Net Profit | 89,000 | | | |",
    "| Net Profit | 53,000 | | | |",
    $content
);

// Lines 405-408: Checklist
$content = str_replace(
    '- [ ] COGS `171,000 MMK` exact match',
    '- [ ] COGS `207,000 MMK` exact match',
    $content
);

$content = str_replace(
    '- [ ] Gross profit `109,000 MMK` exact match',
    '- [ ] Gross profit `73,000 MMK` exact match',
    $content
);

$content = str_replace(
    '- [ ] Net profit `89,000 MMK` exact match',
    '- [ ] Net profit `53,000 MMK` exact match',
    $content
);

if ($content === $original) {
    echo "NO CHANGES MADE\n";
    exit(1);
}

file_put_contents($file, $content);
echo "Changes written successfully.\n\n";

// Verify all corrected lines
$lines = explode("\n", $content);
echo "=== Corrected lines ===\n";
foreach ([211, 212, 225, 226, 283, 284, 285, 286, 315, 316, 318, 405, 406, 408] as $ln) {
    echo "L$ln: " . trim($lines[$ln-1]) . "\n";
}
