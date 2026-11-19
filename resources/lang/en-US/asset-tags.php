<?php

return [
    'quick_structure' => 'XX parent location · KKK global three-digit sequence · PP item type · TTT additional computer information (optional).',
    'full_guide' => 'View full guide',
    'next_sequence' => 'Next global sequence (KKK): :sequence. Use it in the asset tag for this item.',
    'sequence_exhausted' => 'The global three-digit sequence has reached 999.',
    'title' => 'Asset tag guide',
    'format' => 'Tag format',
    'optional' => 'Brackets indicate an optional suffix. When it does not apply, omit the final hyphen as well.',
    'segment' => 'Segment',
    'meaning' => 'Meaning',
    'parts' => [
        'XX' => 'Parent location, usually the state. Examples: NY and NJ.',
        'KKK' => 'Global three-digit sequence. Each number identifies one item across assets and accessories, regardless of the state, item type or suffix.',
        'PP' => 'Item type. LP = laptop, DT = desktop, HS = headset, KB = keyboard, MS = mouse, WC = webcam, DK = docking station. TV = television, SP = speaker, PJ = projector, AP = access point, VC = video conferencing camera.',
        'TTT' => 'Additional information. For computers this can be the operating system: W11 = Windows 11, MAC = macOS, LNX = Linux.',
    ],
    'examples' => 'Examples',
    'laptop_example' => 'Windows 11 laptop in New York.',
    'desktop_example' => 'Linux desktop in New York.',
    'accessory_example' => 'Headset in New York, with no suffix.',
    'storage' => 'Enter the code in the Asset Tag field of each inventory module.',
    'missing_on_item' => 'This item has no asset tag yet. Add it before assigning it.',
];
