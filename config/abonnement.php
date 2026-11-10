<?php

return [
    // Jours entre le passage en impayé et la suspension du cabinet (documentation §9.3).
    'grace_jours' => (int) env('SUBSCRIPTION_GRACE_DAYS', 5),

    // Durée de l'essai gratuit à la création d'un abonnement.
    'essai_jours' => (int) env('SUBSCRIPTION_TRIAL_DAYS', 14),
];
