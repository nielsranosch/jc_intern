<?php

$nav = [
    'musical_leader'    => 'Musikalische Leitung',
    'users'             => 'Mitglieder',
    'back_top'          => 'Nach oben',
    'user_list'         => 'Liste',
    'user_show_own'     => 'Eigene Daten ändern',
    'add_user'          => 'Hinzufügen',
    'roles'             => 'Rollen',
    'dates'             => 'Termine',
    'all'               => 'Alle',
    'dates_calendar'    => 'Kalender',
    'dates_list'        => 'Liste',
    'rehearsal_create'  => 'Probe planen',
    'attendance_last_rehearsal' => 'Letzte Probenteilnahme',
    'gig_create'        => 'Auftritt planen',
    'sheets'            => 'Hefte',
    'sheet_list'        => 'Liste aller Hefte',
    'sheet_list_own'    => 'Eigene Hefte',
    'sheet_create'      => 'Serie hinzufügen',
    'calendar_sync'     => 'Automatische Synchronisation',
    'admin'             => 'Administration',
    'attendance_gigs'     => 'Auftritt-Teilnahmen',
    'title'         => 'Jazzchor der Uni Bonn',
    'mailchecker_overview' => 'Postfach'
];
$a = microtime(true);
if ($a - (int) $a < 0.002) {
    $nav['title'] = 'Jazzchor, der (intern)';
}
return $nav;
