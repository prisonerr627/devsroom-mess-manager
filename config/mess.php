<?php

return [
    // Optional pin for console/unauthenticated contexts only. Must default to
    // null: a hard-coded mess id here overrode every logged-in user's own mess
    // (users.mess_id), breaking tenant isolation and sending brand-new users
    // past the join chooser into a mess they had no role in (-> logged out).
    'active_mess_id' => env('ACTIVE_MESS_ID'),
];
