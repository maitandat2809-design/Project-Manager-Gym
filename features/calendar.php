<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include_once __DIR__ . '/../includes/header.php'; 
?>

<div style="padding-top: 120px; background: var(--bg-cream); min-height: 100vh;">
<section id="calendar" style="padding: 4rem 2rem; max-width: 1440px; margin: 0 auto;">
<style>
    /* ────────────────────────────────────────────────────────────────
       CALENDAR — MAISON EUROPÉENNE  ·  Atelier Design System
       Restrained luxury: ivory, warm slate, burnished gold accents
    ──────────────────────────────────────────────────────────────── */
    @import url('https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Inter:wght@400;500;600;700&display=swap');

    :root {
        --cal-navy:      #1c2340;        /* deep night-navy */
        --cal-gold:      #b08d57;        /* burnished antique gold */
        --cal-gold-lt:   #e0cab98a;
        --cal-cream:     #f7f4ef;        /* warm ivory */
        --cal-paper:     #fdfcfa;        /* parchment white */
        --cal-border:    #e8e2d8;        /* barely-there warm stone */
        --cal-border-dk: #c9bfb0;
        --cal-text:      #1c2340;
        --cal-muted:     #6b6862;
        --cal-shadow:    rgba(28, 35, 64, 0.06);
    }

    /* ── Page background ── */
    div[style*="background: var(--bg-cream)"] {
        background: #f5f2ec !important;
    }

    /* ── Wrapper ── */
    #calendar {
        padding: 3.5rem 2rem 5rem;
        max-width: 1480px;
        margin: 0 auto;
        font-family: 'Inter', sans-serif;
        color: var(--cal-text);
    }

    /* ─────────────── PAGE TITLE ─────────────── */
    .calendar-title-wrapper {
        text-align: center;
        margin-bottom: 4.5rem;
    }
    .calendar-title-wrapper h2 {
        font-family: 'DM Serif Display', serif;
        font-weight: 400;
        font-size: 3.8rem;
        color: var(--cal-navy);
        letter-spacing: -0.5px;
        line-height: 1.15;
        margin-bottom: 0;
    }
    .calendar-title-wrapper h2 em {
        font-style: italic;
        color: var(--cal-gold);
    }
    .calendar-title-wrapper .underline {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin: 1.4rem auto 1.2rem;
    }
    .calendar-title-wrapper .underline::before,
    .calendar-title-wrapper .underline::after {
        content: '';
        display: block;
        width: 60px;
        height: 1px;
        background: var(--cal-gold);
        opacity: 0.6;
    }
    .calendar-title-wrapper .underline-diamond {
        color: var(--cal-gold);
        font-size: 0.55rem;
        opacity: 0.85;
        letter-spacing: 4px;
    }
    .calendar-title-wrapper p {
        font-family: 'Inter', sans-serif;
        color: var(--cal-muted);
        font-size: 1rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        font-weight: 500;
    }

    /* ─────────────── ROSTER PANEL ─────────────── */
    .roster-container {
        background: var(--cal-paper);
        padding: 3rem 3.5rem;
        border: 1px solid var(--cal-border);
        border-top: 3px solid var(--cal-navy);
        box-shadow: 0 4px 40px var(--cal-shadow), 0 1px 0 var(--cal-border-dk);
        margin-bottom: 3rem;
        overflow-x: auto;
        position: relative;
    }
    .roster-container::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--cal-navy) 0%, var(--cal-gold) 50%, var(--cal-navy) 100%);
        pointer-events: none;
    }

    .roster-header-flex {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2.5rem;
        flex-wrap: wrap;
        gap: 1.5rem;
        padding-bottom: 1.8rem;
        border-bottom: 1px solid var(--cal-border);
    }
    .roster-header-flex h2 {
        font-family: 'DM Serif Display', serif;
        font-size: 2.1rem;
        font-weight: 400;
        color: var(--cal-navy);
        margin: 0;
        letter-spacing: -0.3px;
    }

    /* ─────────────── WEEK NAV ─────────────── */
    .week-nav-group {
        display: flex;
        align-items: center;
        gap: 0;
    }
    .btn-nav-week {
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        font-size: 0.82rem;
        letter-spacing: 1px;
        text-transform: uppercase;
        color: var(--cal-navy);
        border: 1px solid var(--cal-border-dk);
        background: transparent;
        padding: 10px 20px;
        cursor: pointer;
        transition: all 0.22s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-nav-week:hover {
        background: var(--cal-navy);
        color: var(--cal-gold);
        border-color: var(--cal-navy);
    }
    .week-indicator {
        font-family: 'Inter', sans-serif;
        font-weight: 500;
        font-size: 0.9rem;
        padding: 10px 22px;
        background: var(--cal-cream);
        border-top: 1px solid var(--cal-border-dk);
        border-bottom: 1px solid var(--cal-border-dk);
        color: var(--cal-navy);
        letter-spacing: 0.05em;
        white-space: nowrap;
    }

    /* ─────────────── FILTER PANEL ─────────────── */
    .filter-panel-luxury {
        display: flex;
        gap: 14px;
        margin-bottom: 2rem;
        align-items: center;
        background: var(--cal-cream);
        padding: 1.1rem 1.6rem;
        border: 1px solid var(--cal-border);
        flex-wrap: wrap;
    }
    .filter-panel-luxury strong {
        font-family: 'DM Serif Display', serif;
        font-style: italic;
        font-size: 1.25rem;
        color: var(--cal-navy);
        font-weight: 400;
        white-space: nowrap;
    }
    .filter-input-luxury,
    .filter-select-luxury {
        font-family: 'Inter', sans-serif;
        font-size: 0.92rem;
        padding: 10px 15px;
        border: 1px solid var(--cal-border-dk);
        background: var(--cal-paper);
        color: var(--cal-text);
        outline: none;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .filter-input-luxury { width: 220px; }
    .filter-select-luxury { width: 180px; }
    .filter-input-luxury:focus,
    .filter-select-luxury:focus {
        border-color: var(--cal-gold);
        box-shadow: 0 0 0 3px rgba(176, 141, 87, 0.1);
    }

    /* ─────────────── TABLE ─────────────── */
    .luxury-roster-table {
        width: 100%;
        border-collapse: collapse;
        font-family: 'Inter', sans-serif;
    }
    .luxury-roster-table th {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 0.82rem;
        letter-spacing: 0.8px;
        text-transform: uppercase;
        color: var(--cal-navy);
        background: var(--cal-cream);
        padding: 16px 14px;
        border: 1px solid var(--cal-border);
    }
    .luxury-roster-table th.date-sub-header {
        background: #eee8de;
        padding: 9px 14px;
        font-weight: 600;
        color: var(--cal-navy);
        letter-spacing: 0.3px;
        border: 1px solid var(--cal-border);
        font-family: 'Inter', sans-serif;
        font-style: normal;
        font-size: 0.9rem;
    }
    .luxury-roster-table td {
        border: 1px solid var(--cal-border);
        padding: 14px 12px;
        background: var(--cal-paper);
        vertical-align: top;
        font-size: 0.95rem;
        transition: background 0.15s ease;
    }
    .luxury-roster-table tr:nth-child(even) td.staff-name-col,
    .luxury-roster-table tr:nth-child(even) td.booking-action-col {
        background: #faf8f4;
    }
    .luxury-roster-table tr:hover td {
        background: rgba(176, 141, 87, 0.025);
    }

    .staff-name-col {
        font-family: 'DM Serif Display', serif;
        font-weight: 400;
        font-size: 1.3rem;
        color: var(--cal-navy);
        white-space: nowrap;
        text-align: left;
        padding: 16px 20px;
        letter-spacing: 0px;
    }
    .booking-action-col { white-space: nowrap; }

    /* ─────────────── BOOK BUTTON ─────────────── */
    .btn-book-roster {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--cal-navy);
        border: 1px solid var(--cal-navy);
        background: transparent;
        padding: 8px 14px;
        cursor: pointer;
        transition: all 0.22s ease;
        text-decoration: none;
        display: inline-block;
    }
    .btn-book-roster:hover {
        background: var(--cal-navy);
        color: var(--cal-gold);
    }

    /* ─────────────── BOOKING CARDS ─────────────── */
    .booking-card {
        background: var(--cal-paper);
        border: 1px solid var(--cal-border);
        padding: 13px 14px;
        margin-bottom: 10px;
        position: relative;
        text-align: left;
        transition: all 0.22s ease;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        padding-left: 17px;
    }
    .booking-card:last-child { margin-bottom: 0; }
    .booking-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(28, 35, 64, 0.06);
        border-color: var(--cal-gold);
    }
    .booking-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 3px; height: 100%;
    }
    .booking-card.staff::before    { background: var(--cal-navy); }
    .booking-card.personal::before { background: var(--cal-gold); }

    .booking-time {
        font-family: 'Inter', sans-serif;
        font-weight: 700;
        font-size: 1rem;
        color: var(--cal-navy);
        margin-bottom: 9px;
        display: flex;
        align-items: center;
        gap: 7px;
        border-bottom: 1px solid var(--cal-border);
        padding-bottom: 7px;
        letter-spacing: 0px;
    }
    .booking-time i { color: var(--cal-gold); font-size: 0.8rem; }

    .booking-badge {
        padding: 4px 0;
        margin-bottom: 4px;
        border: none;
        background: transparent;
    }
    .booking-badge:last-child { margin-bottom: 0; }

    .badge-title {
        font-size: 0.84rem;
        font-weight: 700;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
        gap: 5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .booking-user .badge-title  { color: var(--cal-navy); }
    .booking-coach .badge-title { color: var(--cal-gold); }

    .badge-info {
        font-size: 0.84rem;
        color: var(--cal-muted);
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .badge-info span { display: flex; align-items: center; gap: 5px; }
    .badge-info i {
        width: 12px;
        text-align: center;
        color: var(--cal-gold);
        opacity: 0.7;
    }

    /* ─────────────── STATUS LABELS ─────────────── */
    .status-available {
        font-family: 'Inter', sans-serif;
        font-style: normal;
        color: var(--cal-gold);
        background: transparent;
        padding: 5px 0;
        display: inline-block;
        font-weight: 600;
        font-size: 0.9rem;
        letter-spacing: 0.02em;
        border: none;
    }
    .status-available::before {
        content: "◆ ";
        font-size: 0.55rem;
        color: var(--cal-gold-lt);
        vertical-align: middle;
    }

    .status-off {
        font-family: 'Inter', sans-serif;
        font-style: normal;
        color: #9a9591;
        background: transparent;
        padding: 5px 0;
        display: inline-block;
        font-weight: 500;
        font-size: 0.88rem;
        border: none;
    }
    .status-off::before {
        content: "— ";
        color: #ccc7bf;
    }

    /* ─────────────── CLASS INFO BLOCK ─────────────── */
    .class-info-block {
        font-size: 0.78rem;
        background: var(--cal-cream);
        padding: 9px 10px;
        margin-bottom: 9px;
        border-left: 2px solid var(--cal-gold);
        border-top: 1px solid var(--cal-border);
        border-right: 1px solid var(--cal-border);
        border-bottom: 1px solid var(--cal-border);
    }
    .class-info-block strong {
        color: var(--cal-navy);
        display: block;
        margin-bottom: 5px;
        font-size: 0.78rem;
        font-family: 'Jost', sans-serif;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* ─────────────── CROWD TRACKER ─────────────── */
    .crowd-tracker-card {
        background: var(--cal-paper);
        border: 1px solid var(--cal-border);
        border-top: 3px solid var(--cal-navy);
        padding: 2.5rem 3rem;
        margin: 0 auto 3rem;
        max-width: 1480px;
        display: grid;
        grid-template-columns: 1fr 2.5fr;
        gap: 3rem;
        box-shadow: 0 4px 40px var(--cal-shadow);
        position: relative;
        overflow: hidden;
    }
    .crowd-tracker-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--cal-navy) 0%, var(--cal-gold) 50%, var(--cal-navy) 100%);
    }
    @media (max-width: 800px) {
        .crowd-tracker-card { grid-template-columns: 1fr; gap: 2rem; padding: 1.8rem; }
    }

    .live-occupancy-box {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-right: 1px solid var(--cal-border);
        padding-right: 3rem;
        text-align: center;
    }
    @media (max-width: 800px) {
        .live-occupancy-box {
            border-right: none;
            padding-right: 0;
            padding-bottom: 2rem;
            border-bottom: 1px solid var(--cal-border);
        }
    }
    .live-circle {
        position: relative;
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: var(--cal-cream);
        border: 1.5px solid var(--cal-gold);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: 1.4rem;
        box-shadow: 0 0 0 8px rgba(176,141,87,0.06), 0 8px 30px rgba(28,35,64,0.06);
    }
    .live-percent {
        font-family: 'Cormorant Garamond', serif;
        font-size: 2.6rem;
        font-weight: 700;
        color: var(--cal-navy);
        line-height: 1;
        margin-bottom: 4px;
    }
    .live-percent-lbl {
        font-size: 0.6rem;
        font-weight: 600;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: var(--cal-muted);
    }
    .live-pulse {
        position: absolute;
        top: 16px; right: 16px;
        width: 8px; height: 8px;
        border-radius: 50%;
        background: #16a34a;
        box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.4);
        animation: pulseLive 2s infinite;
    }
    @keyframes pulseLive {
        0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7); }
        70%  { transform: scale(1);    box-shadow: 0 0 0 8px rgba(22, 163, 74, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }
    .live-lbl-status {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--cal-navy);
        margin: 5px 0 4px;
    }
    .live-lbl-desc {
        font-size: 0.78rem;
        color: var(--cal-muted);
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    /* ─────────────── HEATMAP ─────────────── */
    .heatmap-box {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .heatmap-title-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.8rem;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--cal-border);
    }
    .heatmap-title-row strong {
        font-family: 'Cormorant Garamond', serif;
        font-size: 1.5rem;
        color: var(--cal-navy);
        font-weight: 600;
    }
    .heatmap-legend {
        display: flex;
        gap: 14px;
        font-size: 0.68rem;
        font-weight: 500;
        color: var(--cal-muted);
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    .legend-item { display: flex; align-items: center; gap: 5px; }
    .legend-box { width: 12px; height: 12px; }
    .heatmap-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
    }
    .heatmap-col {
        display: flex;
        flex-direction: column;
        gap: 8px;
        text-align: center;
    }
    .heatmap-day-label {
        font-family: 'Jost', sans-serif;
        font-size: 0.68rem;
        font-weight: 600;
        color: var(--cal-navy);
        text-transform: uppercase;
        margin-bottom: 4px;
        letter-spacing: 1px;
    }
    .heatmap-slot {
        height: 40px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 600;
        transition: all 0.22s ease;
        cursor: pointer;
        position: relative;
        border: 1px solid rgba(0,0,0,0.05);
        letter-spacing: 0.5px;
    }
    .heatmap-slot::after {
        content: attr(data-tooltip);
        position: absolute;
        bottom: 100%; left: 50%;
        transform: translateX(-50%) translateY(-6px);
        background: var(--cal-navy);
        color: white;
        padding: 7px 11px;
        font-size: 0.68rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.2s ease;
        z-index: 100;
        box-shadow: 0 4px 14px rgba(0,0,0,0.15);
        letter-spacing: 0.3px;
    }
    .heatmap-slot:hover::after {
        opacity: 1;
        transform: translateX(-50%) translateY(-3px);
    }
    .heatmap-slot:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px var(--cal-shadow);
    }
    .load-low  { background: #f7f4ef; color: #9a9389; border-color: var(--cal-border); }
    .load-med  { background: #ede5d8; color: #8a6c3b; border-color: #d9c9af; }
    .load-high { background: var(--cal-navy); color: var(--cal-gold); border-color: var(--cal-navy); }
</style>

    <div class="calendar-title-wrapper">
        <h2><?php echo __('calendar_title'); ?></h2>
        <div class="underline"><span class="underline-diamond">◆ ◆ ◆</span></div>
        <p><?php echo __('calendar_subtitle'); ?></p>
    </div>

    <!-- Live Crowd & Capacity Heatmap Panel -->
    <?php
    try {
        // Calculate Live occupancy
        $current_hour = (int)date('H');
        $current_dow = (int)date('w') + 1; // 1 = Sunday, ..., 7 = Saturday
        $today_str = date('Y-m-d');
        
        $stmt_crowd = $pdo->prepare("
            SELECT COUNT(*) 
            FROM bookings 
            WHERE DATE(booking_date) = ? 
              AND HOUR(booking_date) = ? 
              AND status != 'cancelled'
        ");
        $stmt_crowd->execute([$today_str, $current_hour]);
        $active_bookings_now = (int)$stmt_crowd->fetchColumn();
        
        // Fetch 30-day statistics for Heatmap
        $stmt_heatmap = $pdo->prepare("
            SELECT DAYOFWEEK(booking_date) as dow, HOUR(booking_date) as hr, COUNT(*) as cnt
            FROM bookings
            WHERE booking_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND status != 'cancelled'
            GROUP BY dow, hr
        ");
        $stmt_heatmap->execute();
        $heatmap_raw = $stmt_heatmap->fetchAll();
        
        $load_grid = [];
        for ($dow = 1; $dow <= 7; $dow++) {
            $load_grid[$dow] = ['morning' => 0, 'midday' => 0, 'evening' => 0];
        }
        foreach ($heatmap_raw as $row) {
            $dow = (int)$row['dow'];
            $hr = (int)$row['hr'];
            $cnt = (int)$row['cnt'];
            
            if ($hr >= 6 && $hr < 11) {
                $load_grid[$dow]['morning'] += $cnt;
            } elseif ($hr >= 11 && $hr < 16) {
                $load_grid[$dow]['midday'] += $cnt;
            } elseif ($hr >= 16 && $hr < 22) {
                $load_grid[$dow]['evening'] += $cnt;
            }
        }
        
        // Normalize live occupancy percentage based on current period load
        $current_period = 'morning';
        if ($current_hour >= 11 && $current_hour < 16) {
            $current_period = 'midday';
        } elseif ($current_hour >= 16 && $current_hour < 22) {
            $current_period = 'evening';
        }
        
        $period_load = isset($load_grid[$current_dow][$current_period]) ? $load_grid[$current_dow][$current_period] : 0;
        $live_capacity_percent = min(98, max(5, ($period_load * 12) + ($active_bookings_now * 15) + rand(3, 8)));
        
        // Determine status labels
        if ($live_capacity_percent < 30) {
            $live_status_text = __('crowd_peaceful');
            $pulse_color = '#16a34a';
        } elseif ($live_capacity_percent < 70) {
            $live_status_text = __('crowd_moderate');
            $pulse_color = '#d97706';
        } else {
            $live_status_text = __('crowd_busy');
            $pulse_color = '#dc2626';
        }
        
        // Days map for displaying DOW
        $dow_labels = [
            2 => __('day_monday'),
            3 => __('day_tuesday'),
            4 => __('day_wednesday'),
            5 => __('day_thursday'),
            6 => __('day_friday'),
            7 => __('day_saturday'),
            1 => __('day_sunday')
        ];
        
        // Helper to output CSS class & label based on load size
        function getLoadClass($val) {
            if ($val == 0) return 'load-low';
            if ($val < 3) return 'load-med';
            return 'load-high';
        }
        
        function getLoadText($val) {
            if ($val == 0) return 'Low / Vắng';
            if ($val < 3) return 'Normal / Vừa';
            return 'Peak / Đông';
        }
        
    } catch (Exception $e) {
        // Fallback default variables on error
        $live_capacity_percent = 15;
        $live_status_text = __('crowd_peaceful');
        $pulse_color = '#16a34a';
    }
    ?>
    <div class="crowd-tracker-card">
        <div class="live-occupancy-box">
            <div class="live-circle">
                <div class="live-pulse" style="background: <?php echo $pulse_color; ?>; box-shadow: 0 0 0 0 <?php echo $pulse_color; ?>80;"></div>
                <span class="live-percent"><?php echo $live_capacity_percent; ?>%</span>
                <span class="live-percent-lbl"><?php echo __('status'); ?></span>
            </div>
            <h4 class="live-lbl-status"><?php echo __('crowd_status_live'); ?></h4>
            <p class="live-lbl-desc"><?php echo $live_status_text; ?></p>
        </div>
        
        <div class="heatmap-box">
            <div class="heatmap-title-row">
                <strong><?php echo __('weekly_load_forecast'); ?></strong>
                <div class="heatmap-legend">
                    <div class="legend-item"><span class="legend-box load-low"></span><span>Low</span></div>
                    <div class="legend-item"><span class="legend-box load-med"></span><span>Active</span></div>
                    <div class="legend-item"><span class="legend-box load-high"></span><span>Peak</span></div>
                </div>
            </div>
            
            <div class="heatmap-grid">
                <?php foreach ([2, 3, 4, 5, 6, 7, 1] as $d_idx): ?>
                    <div class="heatmap-col">
                        <span class="heatmap-day-label"><?php echo substr($dow_labels[$d_idx], 0, 3); ?></span>
                        
                        <?php 
                        $m_load = $load_grid[$d_idx]['morning'];
                        $md_load = $load_grid[$d_idx]['midday'];
                        $e_load = $load_grid[$d_idx]['evening'];
                        ?>
                        
                        <!-- Morning (06:00 - 11:00) -->
                        <div class="heatmap-slot <?php echo getLoadClass($m_load); ?>" 
                             data-tooltip="Morning (06:00-11:00): <?php echo getLoadText($m_load); ?>">
                            <span>AM</span>
                        </div>
                        
                        <!-- Midday (11:00 - 16:00) -->
                        <div class="heatmap-slot <?php echo getLoadClass($md_load); ?>" 
                             data-tooltip="Midday (11:00-16:00): <?php echo getLoadText($md_load); ?>">
                            <span>NOON</span>
                        </div>
                        
                        <!-- Evening (16:00 - 22:00) -->
                        <div class="heatmap-slot <?php echo getLoadClass($e_load); ?>" 
                             data-tooltip="Evening (16:00-22:00): <?php echo getLoadText($e_load); ?>">
                            <span>PM</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    
    <?php
    // Weekly roster logic
    $week_offset = isset($_GET['week_offset']) ? (int)$_GET['week_offset'] : 0;
    $monday = new DateTime("Monday this week");
    if ($week_offset !== 0) {
        $monday->modify("$week_offset week");
    }
    $sunday = clone $monday;
    $sunday->modify('+6 days');

    $days = [];
    for ($i = 0; $i < 7; $i++) {
        $date = clone $monday;
        $date->modify("+$i days");
        $days[] = $date;
    }

    $stmt_c = $pdo->query("SELECT c.id, u.name, COALESCE(c.zalo, u.email) as contact FROM coaches c JOIN users u ON c.user_id = u.id");
    $coaches = $stmt_c->fetchAll();

    $start_date_str = $monday->format('Y-m-d 00:00:00');
    $end_date_str = $sunday->format('Y-m-d 23:59:59');

    $stmt_b = $pdo->prepare("
        SELECT b.*, u.name as client_name, u.email as client_email, COALESCE(b.user_phone, u.phone) as client_phone,
               c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone,
               c.room_number as coach_room_number
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        JOIN coaches c ON b.coach_id = c.id
        JOIN users c_u ON c.user_id = c_u.id
        WHERE b.booking_date >= ? AND b.booking_date <= ? AND b.status != 'cancelled'
    ");
    $stmt_b->execute([$start_date_str, $end_date_str]);
    $bookings = $stmt_b->fetchAll();

    $roster = [];
    foreach ($bookings as $b) {
        $date = date('Y-m-d', strtotime($b['booking_date']));
        $time = date('H:i', strtotime($b['booking_date']));
        $end_time = date('H:i', strtotime($b['booking_date']) + 3600);
        
        $client_phone = htmlspecialchars($b['client_phone'] ?: 'N/A');
        $client_email = htmlspecialchars($b['client_email'] ?? '');
        $client_name = $b['client_name'] ? htmlspecialchars($b['client_name']) : __('open_class');
        $coach_phone = htmlspecialchars($b['coach_phone'] ?: 'N/A');
        $coach_email = htmlspecialchars($b['coach_email'] ?? '');
        $coach_room = !empty($b['room_number']) ? $b['room_number'] : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'N/A');
        
        $class_info = "";
        if (!empty($b['class_name'])) {
            $att_color = $b['attendance'] == 'present' ? 'color: #16a34a;' : ($b['attendance'] == 'absent' ? 'color: #dc2626;' : 'color: #f59e0b;');
            $att_text = $b['attendance'] == 'present' ? __('present_label') : ($b['attendance'] == 'absent' ? __('absent_label') : __('pending'));
            
            $class_info = "
            <div class='class-info-block'>
                <strong><i class='fas fa-chalkboard-teacher'></i> ".htmlspecialchars($b['class_name'])."</strong>
                ".(!empty($b['specialty']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-tag' style='width: 14px;'></i> ".htmlspecialchars($b['specialty'])."</div>" : "")."
                ".(!empty($b['room_number']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-door-open' style='width: 14px;'></i> ".htmlspecialchars($b['room_number'])."</div>" : "")."
                ".(!empty($b['training_plan']) ? "<div style='color: #475569; margin-bottom: 4px; font-style: italic;'><i class='fas fa-clipboard-list' style='width: 14px;'></i> ".htmlspecialchars($b['training_plan'])."</div>" : "")."
                <div style='{$att_color} font-weight: 700;'><i class='fas fa-user-check' style='width: 14px;'></i> {$att_text}</div>
            </div>";
        }
        
        $time_str = "
        <div class='booking-card staff'>
            <div class='booking-time'>
                <i class='far fa-clock'></i> {$time} - {$end_time}
            </div>
            
            {$class_info}
            
            <div class='booking-badge booking-user'>
                <div class='badge-title'>
                    <i class='fas fa-users'></i> {$client_name}
                </div>
                <div class='badge-info'>
                    <span><i class='fas fa-phone-alt'></i> {$client_phone}</span>
                    <span><i class='fas fa-envelope'></i> {$client_email}</span>
                </div>
            </div>

            <div class='booking-badge booking-coach'>
                <div class='badge-title'>
                    <i class='fas fa-dumbbell'></i> ".htmlspecialchars($b['coach_name'])."
                </div>
                <div class='badge-info'>
                    <span><i class='fas fa-phone-alt'></i> {$coach_phone}</span>
                    <span><i class='fas fa-envelope'></i> {$coach_email}</span>
                    <span><i class='fas fa-door-open'></i> ".htmlspecialchars($coach_room)."</span>
                </div>
            </div>
        </div>";
        
        $roster[$b['coach_id']][$date][] = $time_str;
    }

    $day_names = [
        __('day_monday'),
        __('day_tuesday'),
        __('day_wednesday'),
        __('day_thursday'),
        __('day_friday'),
        __('day_saturday'),
        __('day_sunday')
    ];
    ?>
    
    <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && !in_array($_SESSION['role'], ['admin', 'manager'])): 
        $user_id = $_SESSION['user_id'];
        $stmt_user_b = $pdo->prepare("
            SELECT b.*, u.name as client_name, u.email as client_email, COALESCE(b.user_phone, u.phone) as client_phone,
                   c_u.name as coach_name, c_u.email as coach_email, c_u.phone as coach_phone,
                   c.room_number as coach_room_number
            FROM bookings b 
            JOIN users u ON b.user_id = u.id
            JOIN coaches c ON b.coach_id = c.id
            JOIN users c_u ON c.user_id = c_u.id
            WHERE b.user_id = ? AND b.booking_date >= ? AND b.booking_date <= ? AND b.status != 'cancelled'
        ");
        $stmt_user_b->execute([$user_id, $start_date_str, $end_date_str]);
        $user_bookings = $stmt_user_b->fetchAll();

        $user_roster = [];
        foreach ($user_bookings as $b) {
            $date = date('Y-m-d', strtotime($b['booking_date']));
            $time = date('H:i', strtotime($b['booking_date']));
            $end_time = date('H:i', strtotime($b['booking_date']) + 3600);
            
            $client_phone = htmlspecialchars($b['client_phone'] ?: 'N/A');
            $client_email = htmlspecialchars($b['client_email'] ?? '');
            $client_name = $b['client_name'] ? htmlspecialchars($b['client_name']) : __('open_class');
            $coach_phone = htmlspecialchars($b['coach_phone'] ?: 'N/A');
            $coach_email = htmlspecialchars($b['coach_email'] ?? '');
            $coach_room = !empty($b['room_number']) ? $b['room_number'] : (!empty($b['coach_room_number']) ? $b['coach_room_number'] : 'N/A');
            
            $class_info = "";
            if (!empty($b['class_name'])) {
                $att_color = $b['attendance'] == 'present' ? 'color: #16a34a;' : ($b['attendance'] == 'absent' ? 'color: #dc2626;' : 'color: #f59e0b;');
                $att_text = $b['attendance'] == 'present' ? __('present_label') : ($b['attendance'] == 'absent' ? __('absent_label') : __('pending'));
                
                $class_info = "
                <div class='class-info-block'>
                    <strong><i class='fas fa-chalkboard-teacher'></i> ".htmlspecialchars($b['class_name'])."</strong>
                    ".(!empty($b['specialty']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-tag' style='width: 14px;'></i> ".htmlspecialchars($b['specialty'])."</div>" : "")."
                    ".(!empty($b['room_number']) ? "<div style='color: #475569; margin-bottom: 2px;'><i class='fas fa-door-open' style='width: 14px;'></i> ".htmlspecialchars($b['room_number'])."</div>" : "")."
                    ".(!empty($b['training_plan']) ? "<div style='color: #475569; margin-bottom: 4px; font-style: italic;'><i class='fas fa-clipboard-list' style='width: 14px;'></i> ".htmlspecialchars($b['training_plan'])."</div>" : "")."
                    <div style='{$att_color} font-weight: 700;'><i class='fas fa-user-check' style='width: 14px;'></i> {$att_text}</div>
                </div>";
            }
                
            $time_str = "
            <div class='booking-card personal'>
                <div class='booking-time'>
                    <i class='far fa-clock'></i> {$time} - {$end_time}
                </div>
                
                {$class_info}
                
                <div class='booking-badge booking-user'>
                    <div class='badge-title'>
                        <i class='fas fa-users'></i> {$client_name}
                    </div>
                    <div class='badge-info'>
                        <span><i class='fas fa-phone-alt'></i> {$client_phone}</span>
                        <span><i class='fas fa-envelope'></i> {$client_email}</span>
                    </div>
                </div>

                <div class='booking-badge booking-coach'>
                    <div class='badge-title'>
                        <i class='fas fa-dumbbell'></i> ".htmlspecialchars($b['coach_name'])."
                    </div>
                    <div class='badge-info'>
                        <span><i class='fas fa-phone-alt'></i> {$coach_phone}</span>
                        <span><i class='fas fa-envelope'></i> {$coach_email}</span>
                        <span><i class='fas fa-door-open'></i> ".htmlspecialchars($coach_room)."</span>
                    </div>
                </div>
            </div>";
                
            $user_roster[$date][] = $time_str;
        }
    ?>
    <div class="roster-container">
        <div class="roster-header-flex">
            <h2><?php echo __('my_weekly_roster'); ?></h2>
        </div>
        
        <table class="luxury-roster-table">
            <thead>
                <tr>
                    <th rowspan="2"><?php echo __('student'); ?></th>
                    <?php foreach($day_names as $idx => $dname): ?>
                        <th><?php echo $dname; ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php foreach($days as $d): ?>
                        <th class="date-sub-header"><?php echo $d->format('d-M'); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="staff-name-col"><?php echo htmlspecialchars($_SESSION['user_name']); ?></td>
                    <?php foreach($days as $d): 
                        $d_str = $d->format('Y-m-d');
                        $has_booking = isset($user_roster[$d_str]);
                        if ($has_booking) {
                            $times = implode("", $user_roster[$d_str]);
                            echo "<td>{$times}</td>";
                        } else {
                            echo "<td><span class='status-off'>" . __('off') . "</span></td>";
                        }
                    endforeach; ?>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="roster-container">
        <div class="roster-header-flex">
            <h2><?php echo __('staff_weekly_roster'); ?></h2>
            <div class="week-nav-group">
                <a href="/Gym Manager/features/calendar.php?week_offset=<?php echo $week_offset - 1; ?>" class="btn-nav-week"><i class="fas fa-chevron-left"></i> <?php echo __('previous_week'); ?></a>
                <span class="week-indicator">
                    <?php echo $monday->format('d/m/Y') . ' - ' . $sunday->format('d/m/Y'); ?>
                </span>
                <a href="/Gym Manager/features/calendar.php?week_offset=<?php echo $week_offset + 1; ?>" class="btn-nav-week"><?php echo __('next_week'); ?> <i class="fas fa-chevron-right"></i></a>
            </div>
        </div>

        <div class="filter-panel-luxury">
            <strong><?php echo __('find_free_slots'); ?></strong>
            <input type="text" id="coachSearch" placeholder="<?php echo __('search_coach_placeholder'); ?>" class="filter-input-luxury">
            <select id="dayFilter" class="filter-select-luxury">
                <option value="all"><?php echo __('all_days'); ?></option>
                <?php foreach($day_names as $idx => $dname): ?>
                    <option value="<?php echo $idx; ?>"><?php echo $dname; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <table id="staffTable" class="luxury-roster-table">
            <thead>
                <tr>
                    <th rowspan="2"><?php echo __('staff_name'); ?></th>
                    <th rowspan="2"><?php echo __('booking'); ?></th>
                    <?php foreach($day_names as $idx => $dname): ?>
                        <th><?php echo $dname; ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr>
                    <?php foreach($days as $d): ?>
                        <th class="date-sub-header"><?php echo $d->format('d-M'); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($coaches as $idx => $c): ?>
                    <tr>
                        <td class="staff-name-col"><?php echo htmlspecialchars($c['name']); ?></td>
                        <td class="booking-action-col">
                            <a href="/Gym Manager/pages/coach_details.php?id=<?php echo $c['id']; ?>" class="btn-book-roster"><?php echo __('book_now_caps'); ?></a>
                        </td>
                        <?php foreach($days as $d): 
                            $d_str = $d->format('Y-m-d');
                            $has_booking = isset($roster[$c['id']][$d_str]);
                            if ($has_booking) {
                                $times = implode("", $roster[$c['id']][$d_str]);
                                echo "<td>{$times}</td>";
                            } else {
                                echo "<td><span class='status-available'>" . __('available') . "</span></td>";
                            }
                        endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const coachSearch = document.getElementById("coachSearch");
    const dayFilter = document.getElementById("dayFilter");
    const staffTable = document.getElementById("staffTable");
    
    if (staffTable && coachSearch && dayFilter) {
        const rows = staffTable.querySelectorAll("tbody tr");

        function filterTable() {
            const searchText = coachSearch.value.toLowerCase();
            const dayIdx = dayFilter.value;

            rows.forEach(row => {
                const coachName = row.cells[0].innerText.toLowerCase();
                let show = coachName.includes(searchText);

                if (show && dayIdx !== "all") {
                    const cell = row.cells[parseInt(dayIdx) + 2];
                    if (!cell.innerText.includes("AVAILABLE") && !cell.innerText.includes("LỊCH TRỐNG")) {
                        show = false;
                    }
                }
                row.style.display = show ? "" : "none";
            });
        }

        coachSearch.addEventListener("input", filterTable);
        dayFilter.addEventListener("change", filterTable);
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
