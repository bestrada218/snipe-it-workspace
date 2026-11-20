<?php

return [
    'title' => 'Damage report',
    'intro' => 'This status blocks the asset from being assigned. Document what is damaged before saving; it is kept in the asset history.',
    'description' => 'What is damaged',
    'description_placeholder' => 'e.g. Screen cracked and does not power on after the drop',
    'description_required' => 'Describe what is damaged to move the asset into this status.',
    'repairable' => 'Can it be repaired?',
    'repairable_options' => [
        'yes' => 'Yes, it can be repaired',
        'no' => 'No, it cannot be repaired',
        'unknown' => 'Not known yet',
    ],
    'repair_attempted' => 'Was a repair already attempted?',
    'repair_details' => 'What was tried and what happened',
    'repair_details_placeholder' => 'e.g. Vendor replaced the board in Oct; it failed again',
    'yes' => 'Yes',
    'no' => 'No',
    'activity_title' => 'Damage reported',
    'status_change' => 'Status change',
    'statuses' => 'Statuses that require a damage report',
    'statuses_help' => 'Moving an asset into one of these statuses (broken or damaged, no longer assignable) requires documenting what is damaged, whether it can be repaired and whether a repair was attempted.',
];
