<?php
include "../config.php";
include "../config/functions.php";
include "../config/auth_check.php";

// Get admin name from session or database
$adminName = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php $pageTitle = 'Offices Directory | ICTMIS'; ?>
<?php include 'components/head.php'; ?>
<!-- GSAP (GreenSock Animation Platform) for Smooth UI Animations -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
<style>
    :root {
        --accent-primary: #00f2ff;
        --accent-secondary: #00ff88;
        --accent-purple: #d946ef;
        --bg-dark: #0f172a;
        --bg-darker: #010409;
        --card-bg: rgba(30, 41, 59, 0.7);
        --text-bright: #f8fafc;
        --text-dim: #94a3b8;
        --neon-blue: #00f2ff;
        --neon-glow: 0 0 15px rgba(0, 242, 255, 0.3);
        
        --color-admin: #8b5cf6;
        --color-health: #10b981;
        --color-treasury: #f59e0b;
        --color-mayor: #ef4444;
        --color-other: #64748b;
        --color-social: #ec4899;
    }

    body {
        background: #f8fafc;
        color: #1e293b;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        overflow-x: clip;
        position: relative;
    }

    /* Light Mode Adjustments (Default based on image) */
    .main-content {
        background: #f1f5f9;
        min-height: 100vh;
    }

    /* Header Stat Cards */
    .header-stat-card {
        background: white;
        border-radius: 16px;
        padding: 16px 24px;
        display: flex;
        align-items: center;
        gap: 16px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.05);
        min-width: 180px;
    }

    .header-stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.4rem;
        background: #f0fdfa;
        color: #0d9488;
    }

    .header-stat-icon.offices {
        background: #eff6ff;
        color: #2563eb;
    }

    .header-stat-info {
        display: flex;
        flex-direction: column;
    }

    .header-stat-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .header-stat-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0f172a;
    }

    /* Search & Controls */
    .controls-container {
        background: white;
        border-radius: 20px;
        padding: 12px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .search-box {
        position: relative;
        flex: 1;
    }

    .search-box i {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
    }

    .search-box input {
        width: 100%;
        border: none;
        background: #f8fafc;
        padding: 12px 12px 12px 44px;
        border-radius: 12px;
        font-size: 0.95rem;
        color: #1e293b;
    }

    .search-box input:focus {
        outline: none;
        background: #f1f5f9;
    }

    .control-actions {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .btn-control {
        padding: 10px 16px;
        border-radius: 12px;
        font-size: 0.9rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
        border: 1px solid #e2e8f0;
        background: white;
        color: #64748b;
    }

    .btn-control:hover {
        background: #f8fafc;
        border-color: #cbd5e1;
    }

    .btn-control.active {
        background: #f0fdf4;
        color: #16a34a;
        border-color: #bbf7d0;
    }

    .btn-refresh {
        color: #0d9488;
        border-color: #99f6e4;
    }

    .btn-refresh:hover {
        background: #f0fdfa;
    }

    /* Filter Chips */
    .filter-chips {
        display: flex;
        gap: 10px;
        margin-bottom: 32px;
        flex-wrap: wrap;
    }

    .filter-chip {
        padding: 8px 18px;
        border-radius: 12px;
        background: white;
        border: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 0.85rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-chip:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .filter-chip.active {
        background: #00ff8820;
        color: #059669;
        border-color: #00ff88;
        box-shadow: 0 4px 12px rgba(0, 255, 136, 0.1);
    }

    .filter-chip.active::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        width: 12px;
        height: 2px;
        background: #00ff88;
        border-radius: 2px;
    }

    /* Office Grid */
    .office-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
    }

    .office-card {
        background: white;
        border-radius: 24px;
        padding: 24px;
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        gap: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }

    .office-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
    }

    .office-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: var(--accent-primary);
        border-radius: 24px 24px 0 0;
    }

    .card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        background: #eff6ff;
        color: #3b82f6;
    }

    .unit-badge {
        background: #f0fdf4;
        color: #16a34a;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .office-card.cat-mayor::before { background: var(--color-mayor); }
    .office-card.cat-mayor .icon-box { background: #fef2f2; color: #ef4444; }
    
    .office-card.cat-health::before { background: var(--color-health); }
    .office-card.cat-health .icon-box { background: #f0fdf4; color: #10b981; }

    .office-card.cat-treasury::before { background: var(--color-treasury); }
    .office-card.cat-treasury .icon-box { background: #fffbeb; color: #f59e0b; }

    .office-card.cat-admin::before { background: var(--color-admin); }
    .office-card.cat-admin .icon-box { background: #f5f3ff; color: #8b5cf6; }

    .office-card.cat-social::before { background: var(--color-social); }
    .office-card.cat-social .icon-box { background: #fdf2f8; color: #ec4899; }

    .office-card.cat-other::before { background: var(--color-other); }
    .office-card.cat-other .icon-box { background: #f8fafc; color: #64748b; }

    .card-body {
        padding: 0;
    }

    .office-title {
        font-size: 1.15rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 8px 0;
        line-height: 1.4;
    }

    .category-line {
        width: 40px;
        height: 8px;
        background: #f1f5f9;
        border-radius: 4px;
        margin-bottom: 16px;
    }

    .update-section {
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
        margin-top: auto;
    }

    .update-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        display: block;
        margin-bottom: 4px;
    }

    .update-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: #10b981;
    }

    .card-actions {
        display: flex;
        gap: 12px;
        margin-top: 16px;
    }

    .action-btn {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #64748b;
        transition: all 0.2s;
        text-decoration: none;
    }

    .action-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #cbd5e1;
    }

    /* Dark Mode (based on first image) */
    body.dark-mode {
        background: #1e293b;
        color: #f8fafc;
    }

    body.dark-mode .main-content {
        background: #0f172a;
    }

    body.dark-mode .header-stat-card,
    body.dark-mode .controls-container,
    body.dark-mode .office-card,
    body.dark-mode .filter-chip {
        background: #1e293b;
        border-color: rgba(255, 255, 255, 0.05);
    }

    body.dark-mode .header-stat-value,
    body.dark-mode .office-title,
    body.dark-mode .search-box input {
        color: #f8fafc;
    }

    body.dark-mode .search-box input {
        background: #0f172a;
    }

    body.dark-mode .btn-control,
    body.dark-mode .action-btn {
        background: #0f172a;
        border-color: rgba(255, 255, 255, 0.1);
        color: #94a3b8;
    }

    body.dark-mode .category-line {
        background: #0f172a;
    }

    body.dark-mode .update-section {
        border-color: rgba(255, 255, 255, 0.05);
    }

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 10px;
    }
    body.dark-mode ::-webkit-scrollbar-thumb {
        background: #334155;
    }

    /* Quick View Sidebar (Preserved and Styled) */
    .quick-view-panel {
        position: fixed;
        right: -400px;
        top: 0;
        width: 360px;
        height: 100vh;
        background: rgba(1, 4, 9, 0.95);
        backdrop-filter: blur(20px);
        border-left: 1px solid rgba(0, 242, 255, 0.2);
        z-index: 3000;
        transition: right 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        padding: 30px;
        color: white;
        box-shadow: -20px 0 50px rgba(0, 0, 0, 0.8);
        overflow-y: auto;
    }
    .quick-view-panel.active { right: 0; }
    .qv-header { border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 25px; margin-bottom: 25px; }
    .qv-title { font-size: 1.4rem; font-weight: 800; color: var(--neon-blue); text-transform: uppercase; margin-bottom: 10px; }
    .qv-stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
    .qv-stat-item { background: rgba(255, 255, 255, 0.03); padding: 15px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.05); }
    .qv-label { font-size: 0.65rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 5px; display: block; }
    .qv-value { font-size: 1.1rem; font-weight: 700; }
    .qv-close { position: absolute; top: 20px; right: 20px; cursor: pointer; color: var(--text-dim); font-size: 1.8rem; transition: 0.3s; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: rgba(255,255,255,0.05); }
    .qv-close:hover { color: white; background: rgba(255,0,0,0.2); }

    .modal-backdrop.show {
        background: rgba(0, 0, 0, 0.78);
    }
    .modal-content.bg-dark {
        background: rgba(3, 7, 17, 0.97);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(0, 0, 0, 0.55);
    }
    .modal-body {
        padding: 0;
    }

    /* Task Manager Modal (Preserved) */
    .tm-container {
        display: flex;
        height: 720px;
        background: linear-gradient(180deg, rgba(5, 10, 20, 0.98) 0%, rgba(0, 0, 0, 0.95) 100%);
        box-shadow: inset 0 0 80px rgba(0, 0, 0, 0.75);
        border-radius: 24px;
        overflow: hidden;
    }
    .tm-sidebar {
        width: 300px;
        background: linear-gradient(180deg, rgba(10, 16, 30, 0.98), rgba(5, 8, 18, 0.98));
        border-right: 1px solid rgba(255, 255, 255, 0.08);
        overflow-y: auto;
        box-shadow: inset -4px 0 30px rgba(0, 0, 0, 0.4);
    }
    .tm-sidebar::-webkit-scrollbar {
        width: 8px;
    }
    .tm-sidebar::-webkit-scrollbar-thumb {
        background: rgba(0, 242, 255, 0.2);
        border-radius: 999px;
    }
    .tm-item {
        padding: 18px 22px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        cursor: pointer;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        gap: 14px;
        background: rgba(255, 255, 255, 0.02);
        position: relative;
        color: #ffffff !important;
    }
    .tm-item:hover {
        background: rgba(0, 242, 255, 0.14);
        transform: translateX(2px);
    }
    .tm-item.active {
        background: rgba(0, 242, 255, 0.22);
        border-left: 4px solid rgba(0, 242, 255, 0.95);
    }
    .tm-item.active .tm-item-name,
    .tm-item.active .tm-item-sub,
    .tm-item .tm-item-name,
    .tm-item .tm-item-sub {
        color: #ffffff !important;
    }
    .tm-item-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        background: rgba(0, 242, 255, 0.08);
        display: grid;
        place-items: center;
        color: var(--neon-blue);
        flex-shrink: 0;
        font-size: 1.1rem;
        border: 1px solid rgba(0, 242, 255, 0.14);
        box-shadow: inset 0 0 12px rgba(0, 242, 255, 0.08);
    }
    .tm-item-info {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .tm-item-name {
        font-size: 1rem;
        font-weight: 700;
        color: #f8fafc;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .tm-item-sub {
        font-size: 0.8rem;
        color: #94a3b8;
        margin-top: 4px;
        text-transform: capitalize;
    }
    .tm-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: linear-gradient(180deg, rgba(3, 7, 17, 0.98), rgba(0, 0, 0, 1));
        overflow: hidden;
    }
    .tm-header {
        padding: 28px 30px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        background: rgba(255, 255, 255, 0.02);
    }
    .tm-header h2 {
        margin: 0;
        font-size: 1.75rem;
        letter-spacing: -0.03em;
        color: #f8fafc;
    }
    .tm-header .text-info {
        font-size: 0.8rem;
        letter-spacing: 0.16em;
        color: #7dd3fc;
    }
    .btn-close-white {
        filter: drop-shadow(0 0 6px rgba(255, 255, 255, 0.1));
    }
    .tm-content-body {
        flex: 1;
        display: flex;
        overflow: hidden;
    }
    .tm-comp-sidebar {
        width: 240px;
        background: rgba(255, 255, 255, 0.02);
        border-right: 1px solid rgba(255, 255, 255, 0.06);
        padding: 16px 0;
        overflow-y: auto;
    }
    .tm-comp-item {
        padding: 18px 24px;
        cursor: pointer;
        border-left: 3px solid transparent;
        transition: all 0.25s ease;
        color: #ffffff !important;
        font-size: 0.95rem;
        display: block;
    }
    .tm-comp-item:hover {
        background: rgba(0, 242, 255, 0.12);
        color: #ffffff !important;
    }
    .tm-comp-item.active {
        background: rgba(0, 242, 255, 0.22);
        border-left-color: var(--neon-blue);
        color: #ffffff !important;
    }
    .tm-comp-item .small.fw-bold,
    .tm-comp-item.active .small.fw-bold {
        color: #ffffff !important;
    }
    .tm-comp-item .text-muted,
    .tm-comp-item.active .text-muted {
        color: #e2e8f0 !important;
    }
    .tm-details-area {
        flex: 1;
        padding: 30px;
        overflow-y: auto;
    }
    .tm-details-area .qv-stat-item {
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 22px 24px;
        margin-bottom: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
    }
    .tm-details-area .detail-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px;
        margin-bottom: 24px;
    }
    .tm-details-area .detail-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 18px;
        padding: 22px;
    }
    .detail-card span {
        display: block;
        font-size: 0.72rem;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.12em;
        margin-bottom: 10px;
    }
    .detail-card strong {
        display: block;
        font-size: 1.2rem;
        color: #f8fafc;
        font-weight: 800;
        line-height: 1.1;
    }
    .tm-graph-container {
        height: 300px;
        background: radial-gradient(circle at top left, rgba(0, 242, 255, 0.08), transparent 45%),
                    linear-gradient(180deg, rgba(15, 23, 42, 0.95), rgba(5, 8, 18, 0.98));
        border: 1px solid rgba(0, 242, 255, 0.14);
        border-radius: 18px;
        position: relative;
        margin-bottom: 30px;
        overflow: hidden;
    }
    .tm-graph-container::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 20% 20%, rgba(0, 242, 255, 0.12), transparent 35%);
        pointer-events: none;
    }
    .tm-no-data {
        height: 100%;
        display: grid;
        place-items: center;
        color: var(--text-dim);
        font-size: 0.95rem;
        text-align: center;
        padding: 20px;
    }

    /* Highlight class for search */
    .office-card.highlight {
        border-color: var(--neon-blue);
        box-shadow: 0 0 25px rgba(0, 242, 255, 0.3);
        transform: scale(1.02);
    }

    /* Category Filter Chips */
    .filter-chips {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .filter-chip {
        padding: 10px 18px;
        border-radius: 25px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.05) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: var(--text-dim);
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    .filter-chip::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
        transition: left 0.5s ease;
    }

    .filter-chip:hover {
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.12) 0%, rgba(255, 255, 255, 0.08) 100%);
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .filter-chip:hover::before {
        left: 100%;
    }

    .filter-chip.active {
        background: linear-gradient(135deg, var(--neon-blue) 0%, var(--accent-secondary) 100%);
        border-color: var(--neon-blue);
        color: #000;
        box-shadow: 
            0 0 20px rgba(0, 242, 255, 0.4),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
        font-weight: 700;
    }

    /* Sort Dropdown Custom Style */
    .sort-select {
        background: linear-gradient(145deg, rgba(0, 0, 0, 0.4) 0%, rgba(15, 23, 42, 0.5) 100%);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 10px 16px;
        color: white;
        font-size: 0.9rem;
        outline: none;
        cursor: pointer;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        min-width: 140px;
    }
    .sort-select:focus {
        border-color: var(--neon-blue);
        box-shadow: 0 0 15px rgba(0, 242, 255, 0.2);
        background: linear-gradient(145deg, rgba(0, 0, 0, 0.5) 0%, rgba(15, 23, 42, 0.6) 100%);
    }

    .sort-select option {
        background: #1e293b;
        color: white;
    }

    /* Refresh Button Animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .refresh-btn.spinning i {
        animation: spin 1s linear infinite;
    }

    /* Empty State */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 80px 40px;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.03) 0%, rgba(255, 255, 255, 0.01) 100%);
        border: 2px dashed rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        display: none;
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    .empty-state::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: conic-gradient(from 0deg, transparent, rgba(0, 242, 255, 0.05), transparent 120deg);
        animation: rotate 8s linear infinite;
        opacity: 0.3;
    }

    .empty-state i {
        font-size: 4rem;
        color: var(--text-dim);
        margin-bottom: 24px;
        position: relative;
        z-index: 2;
        text-shadow: 0 0 20px rgba(0, 242, 255, 0.2);
    }
    .empty-state h4 { 
        color: white; 
        margin-bottom: 12px;
        font-weight: 700;
        position: relative;
        z-index: 2;
    }
    .empty-state p { 
        color: var(--text-dim);
        position: relative;
        z-index: 2;
        font-size: 0.95rem;
    }

    /* List View Modifier */
    .office-grid.list-view {
        grid-template-columns: 1fr;
    }
    .office-grid.list-view .office-card {
        flex-direction: row;
        align-items: center;
        padding: 12px 24px;
        gap: 20px;
    }
    .office-grid.list-view .office-card::before {
        width: 4px;
        height: 100%;
    }
    .office-grid.list-view .office-card .count-badge {
        position: static;
        order: 3;
        min-width: 80px;
        text-align: center;
    }
    .office-grid.list-view .office-card .office-card-header {
        order: 1;
    }
    .office-grid.list-view .office-card .office-info-wrapper {
        order: 2;
        flex-grow: 1;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .office-grid.list-view .office-card .office-stats {
        margin: 0;
        order: 4;
        min-width: 150px;
    }
    .office-grid.list-view .office-card .office-actions-mini {
        margin: 0;
        order: 5;
    }

    .office-actions-mini {
        display: flex;
        gap: 8px;
        margin-top: 10px;
    }

    .action-btn-mini {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        background: linear-gradient(145deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.05) 100%);
        color: var(--text-dim);
        transition: all 0.3s ease;
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    .action-btn-mini::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.1) 50%, transparent 70%);
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .action-btn-mini:hover {
        background: linear-gradient(135deg, var(--accent-primary) 0%, var(--accent-secondary) 100%);
        color: #000;
        transform: translateY(-3px) scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 242, 255, 0.3);
        border-color: var(--neon-blue);
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .office-grid {
            grid-template-columns: 1fr;
            gap: 16px;
            padding: 16px 0;
        }
        
        .controls-header {
            flex-direction: column;
            gap: 16px;
            padding: 16px;
        }
        
        .analytics-summary {
            flex-direction: column;
            gap: 12px;
        }
        
        .filter-chips {
            justify-content: center;
        }
        
        .office-card {
            padding: 20px;
        }
    }

    /* Loading Animation */
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .loading {
        animation: pulse 1.5s ease-in-out infinite;
    }
    /* List View Modifier */
    .office-grid.list-view {
        grid-template-columns: 1fr;
    }
    .office-grid.list-view .office-card {
        flex-direction: row;
        align-items: center;
        padding: 16px 24px;
        gap: 24px;
    }
    .office-grid.list-view .office-card::before {
        width: 4px;
        height: 100%;
        border-radius: 24px 0 0 24px;
    }
    .office-grid.list-view .card-top {
        flex-shrink: 0;
    }
    .office-grid.list-view .card-body {
        flex: 1;
    }
    .office-grid.list-view .category-line {
        margin-bottom: 0;
    }
    .office-grid.list-view .update-section {
        border: none;
        padding: 0;
        margin: 0;
        min-width: 150px;
    }
    .office-grid.list-view .card-actions {
        margin: 0;
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .office-card {
        animation: fadeIn 0.5s ease-out forwards;
    }

    /* Mobile Adjustments */
    @media (max-width: 768px) {
        .d-flex.justify-content-between.align-items-start {
            flex-direction: column;
            gap: 20px;
        }
        .header-stat-card {
            flex: 1;
        }
        .controls-container {
            flex-direction: column;
            align-items: stretch;
        }
        .control-actions {
            flex-wrap: wrap;
        }
    }
    /* Refresh Button Animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .btn-refresh.spinning i, .animate-spin {
        animation: spin 1s linear infinite;
        display: inline-block;
    }
</style>
</head>

<body>

<!-- Quick View Sidebar -->
<div id="quickView" class="quick-view-panel">
    <div class="qv-close" onclick="document.getElementById('quickView').classList.remove('active')">&times;</div>
    <div class="qv-header">
        <div id="qvTitle" class="qv-title">Office Name</div>
        <div id="qvCategory" class="badge bg-primary mt-2">Department</div>
    </div>
    
    <div class="qv-stat-grid">
        <div class="qv-stat-item">
            <span class="qv-label">Total Assets</span>
            <span id="qvAssets" class="qv-value">0</span>
        </div>
        <div class="qv-stat-item">
            <span class="qv-label">System Status</span>
            <span id="qvStatus" class="qv-value text-success">STABLE</span>
        </div>
    </div>

    <div class="mb-4">
        <h6 class="text-uppercase fw-bold mb-3" style="font-size: 0.65rem; color: var(--neon-blue);">Office Profile</h6>
        <div class="qv-stat-item mb-2">
            <span class="qv-label">Head of Office</span>
            <span id="qvHead" class="qv-value" style="font-size: 0.9rem;">Loading...</span>
        </div>
        <div class="qv-stat-item mb-2">
            <span class="qv-label">Contact Number</span>
            <span id="qvContact" class="qv-value" style="font-size: 0.9rem;">Loading...</span>
        </div>
        <div class="qv-stat-item">
            <span class="qv-label">Office Email</span>
            <span id="qvEmail" class="qv-value" style="font-size: 0.9rem;">Loading...</span>
        </div>
    </div>

    <div class="mb-4">
        <h6 class="text-uppercase fw-bold mb-3" style="font-size: 0.65rem; color: #ef4444;">Active Alerts</h6>
        <div id="qvAlertsList" style="font-size: 0.85rem; color: var(--text-dim);">
            <div class="text-center py-2 text-muted small">No active alerts</div>
        </div>
    </div>

    <div class="mb-4">
        <h6 class="text-uppercase fw-bold mb-3" style="font-size: 0.65rem; color: var(--neon-blue);">Top Equipment</h6>
        <div id="qvEquipmentList" style="font-size: 0.85rem; color: var(--text-dim);">
            <div class="text-center py-3"><i class="bi bi-arrow-clockwise animate-spin me-2"></i>Loading equipment list...</div>
        </div>
    </div>

    <div class="d-grid gap-2">
       <button id="viewDevicesBtn" class="btn btn-info rounded-pill"><i class="bi bi-laptop me-2"></i>View Devices</button>
        <button id="qvFullReport" class="btn btn-outline-info rounded-pill">View Full Report</button>
        <button class="btn btn-outline-primary rounded-pill">Request Maintenance</button>
    </div>
</div>

<!-- Devices Modal -->
<div class="modal fade" id="devicesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-dark border-secondary text-white overflow-hidden" style="border-radius: 20px;">
            <div class="modal-body p-0">
                <div class="tm-container">
                    <div class="tm-sidebar" id="tmSidebar">
                        <!-- Sidebar list -->
                    </div>
                    <div class="tm-main" id="tmMain">
                        <!-- Content -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex">
    <!-- SIDEBAR -->
    <?php include 'components/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content flex-grow-1">
        <!-- TOP BAR -->
        <?php include 'components/header.php'; ?>
        
        <div class="content-area p-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div>
                    <h1 class="fw-bold mb-1" style="font-size: 2rem; color: #0f172a; letter-spacing: -0.02em;">Offices Directory</h1>
                    <p class="text-muted mb-0" style="font-size: 0.95rem;">Comprehensive overview of all departmental units and their hardware assets.</p>
                </div>
                <div class="d-flex gap-3">
                    <?php
                    $totalForms = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM ict_forms"))['total'];
                    $officeCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT office_name) as total FROM ict_forms"))['total'];
                    ?>
                    <div class="header-stat-card">
                        <div class="header-stat-icon"><i class="bi bi-box-seam"></i></div>
                        <div class="header-stat-info">
                            <span class="header-stat-label">Total Assets</span>
                            <span class="header-stat-value"><?php echo number_format($totalForms); ?></span>
                        </div>
                    </div>
                    <div class="header-stat-card">
                        <div class="header-stat-icon offices"><i class="bi bi-building"></i></div>
                        <div class="header-stat-info">
                            <span class="header-stat-label">Offices</span>
                            <span class="header-stat-value"><?php echo $officeCount; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Controls Header -->
            <div class="controls-container">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="officeSearch" placeholder="Search by office name or department...">
                </div>
                
                <div class="control-actions">
                    <select id="officeSort" class="btn-control" style="min-width: 140px; cursor: pointer;">
                        <option value="name-asc">Sort: A-Z</option>
                        <option value="name-desc">Sort: Z-A</option>
                        <option value="assets-desc">Sort: Most Assets</option>
                        <option value="assets-asc">Sort: Least Assets</option>
                    </select>

                    <button id="refreshBtn" class="btn-control btn-refresh" title="Refresh Data">
                        <i class="bi bi-arrow-clockwise"></i> Refresh
                    </button>

                    <div class="d-flex gap-2">
                        <button id="gridToggle" class="btn-control active" title="Grid View">
                            <i class="bi bi-grid-3x3-gap"></i> Grid
                        </button>
                        <button id="listToggle" class="btn-control" title="List View">
                            <i class="bi bi-list-task"></i> List
                        </button>
                    </div>
                </div>
            </div>

            <!-- Category Filter Row -->
            <div class="filter-chips">
                <div class="filter-chip active" data-filter="all">
                    <i class="bi bi-grid"></i> All Units
                </div>
                <div class="filter-chip" data-filter="mayor">
                    <i class="bi bi-person-badge"></i> Mayor's Offices
                </div>
                <div class="filter-chip" data-filter="admin">
                    <i class="bi bi-briefcase"></i> Admin & ICT
                </div>
                <div class="filter-chip" data-filter="health">
                    <i class="bi bi-heart-pulse"></i> Health & Social
                </div>
                <div class="filter-chip" data-filter="treasury">
                    <i class="bi bi-cash-stack"></i> Finance & Treasury
                </div>
                <div class="filter-chip" data-filter="other">
                    <i class="bi bi-three-dots"></i> Other Units
                </div>
            </div>

            <!-- Office Grid -->
            <div class="office-grid" id="officeGrid">
                <div id="emptyState" class="empty-state">
                    <i class="bi bi-search"></i>
                    <h4>Walang nahanap na office</h4>
                    <p>Subukang i-adjust ang iyong search term o filters.</p>
                </div>
                <?php
                // Get all registered offices
                $registeredOffices = [];
                
                // Ensure maintenance_reports table exists
                mysqli_query($conn, "CREATE TABLE IF NOT EXISTS maintenance_reports (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    device_id INT NOT NULL,
                    source ENUM('user', 'admin') NOT NULL,
                    office_name VARCHAR(255) NOT NULL,
                    item_name VARCHAR(255) NOT NULL,
                    problem_description TEXT NOT NULL,
                    status ENUM('pending', 'in_progress', 'resolved', 'cancelled') DEFAULT 'pending',
                    urgency ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                    reported_by VARCHAR(255),
                    reported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    resolved_at TIMESTAMP NULL,
                    admin_remarks TEXT
                )");

                $officeSourceQuery = mysqli_query($conn, "SELECT DISTINCT office_name FROM offices WHERE office_name IS NOT NULL AND office_name != '' ORDER BY office_name ASC");
                if ($officeSourceQuery) {
                    while ($o = mysqli_fetch_assoc($officeSourceQuery)) {
                        $registeredOffices[] = $o['office_name'];
                    }
                }

                // Fallback to ict_forms
                $formOfficesQuery = mysqli_query($conn, "SELECT DISTINCT office_name FROM ict_forms WHERE office_name IS NOT NULL AND office_name != '' ORDER BY office_name ASC");
                if ($formOfficesQuery) {
                    while ($o = mysqli_fetch_assoc($formOfficesQuery)) {
                        if (!in_array($o['office_name'], $registeredOffices)) {
                            $registeredOffices[] = $o['office_name'];
                        }
                    }
                }

                // Get counts, latest dates, and maintenance reports
                $officeData = [];
                $query = mysqli_query($conn, "SELECT office_name, COUNT(*) as count, MAX(date_submitted) as latest_date FROM ict_forms GROUP BY office_name");
                while ($row = mysqli_fetch_assoc($query)) {
                    $officeData[$row['office_name']] = [
                        'count' => $row['count'], 
                        'latest_date' => $row['latest_date'],
                        'report_count' => 0
                    ];
                }

                // Get maintenance reports count
                $reportQuery = mysqli_query($conn, "SELECT office_name, COUNT(*) as count FROM maintenance_reports WHERE status != 'resolved' GROUP BY office_name");
                while ($row = mysqli_fetch_assoc($reportQuery)) {
                    if (isset($officeData[$row['office_name']])) {
                        $officeData[$row['office_name']]['report_count'] = $row['count'];
                    } else {
                        $officeData[$row['office_name']] = [
                            'count' => 0,
                            'latest_date' => null,
                            'report_count' => $row['count']
                        ];
                    }
                }

                foreach ($registeredOffices as $name) {
                    $count = $officeData[$name]['count'] ?? 0;
                    $reportCount = $officeData[$name]['report_count'] ?? 0;
                    $latestDate = $officeData[$name]['latest_date'] ?? null;
                    $formattedDate = $latestDate ? date('M d, Y', strtotime($latestDate)) : 'No Data';
                    
                    // Categorization
                    $category = 'other';
                    $icon = 'bi-building';
                    if (stripos($name, 'Mayor') !== false) { 
                        $category = 'mayor'; $icon = 'bi-person-badge'; 
                    } elseif (stripos($name, 'Health') !== false || stripos($name, 'Social') !== false || stripos($name, 'MSWD') !== false) { 
                        $category = 'health'; $icon = 'bi-heart-pulse'; 
                    } elseif (stripos($name, 'Treasury') !== false || stripos($name, 'Accounting') !== false || stripos($name, 'Budget') !== false || stripos($name, 'Tax') !== false) { 
                        $category = 'treasury'; $icon = 'bi-cash-stack'; 
                    } elseif (stripos($name, 'Admin') !== false || stripos($name, 'ICT') !== false || stripos($name, 'HR') !== false || stripos($name, 'Human Resource') !== false) { 
                        $category = 'admin'; $icon = 'bi-briefcase'; 
                    } elseif (stripos($name, 'Agriculture') !== false || stripos($name, 'MENRO') !== false || stripos($name, 'Environment') !== false) { 
                        $category = 'social'; $icon = 'bi-leaf'; 
                    } elseif (stripos($name, 'MDRRMO') !== false || stripos($name, 'Disaster') !== false) {
                        $category = 'other'; $icon = 'bi-shield-check';
                    }

                    $cleanNameAttr = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
                    $urlName = urlencode($name);
                    ?>
                    
                    <div class="office-card cat-<?php echo $category; ?> <?php echo $reportCount > 0 ? 'has-alerts' : ''; ?>" 
                         data-name="<?php echo $cleanNameAttr; ?>" 
                         data-count="<?php echo $count; ?>"
                         data-category="<?php echo $category; ?>"
                         data-reports="<?php echo $reportCount; ?>"
                         onclick="officeManager.showQuickView(this.dataset.name, this.dataset.count, this.dataset.category)">
                        
                        <div class="card-top">
                            <div class="icon-box">
                                <i class="bi <?php echo $icon; ?>"></i>
                            </div>
                            <div class="unit-badge"><?php echo $count; ?> Units</div>
                        </div>

                        <div class="card-body">
                            <h3 class="office-title"><?php echo htmlspecialchars($name); ?></h3>
                            <div class="category-line"></div>
                        </div>

                        <div class="update-section">
                            <span class="update-label">Latest Update</span>
                            <span class="update-value"><?php echo $formattedDate; ?></span>
                        </div>

                        <div class="card-actions" onclick="event.stopPropagation()">
                            <a href="view_office.php?name=<?php echo $urlName; ?>" class="action-btn" title="View Detailed Profile"><i class="bi bi-pc-display"></i></a>
                            <a href="edit_office.php?name=<?php echo $urlName; ?>" class="action-btn" title="Edit Office Details"><i class="bi bi-file-earmark-text"></i></a>
                            <a href="report.php?search=<?php echo $urlName; ?>" class="action-btn" title="Generate Report"><i class="bi bi-bar-chart-line"></i></a>
                        </div>

                        <?php if ($reportCount > 0): ?>
                            <div class="alert-badge" title="<?php echo $reportCount; ?> pending alerts" style="position: absolute; top: -10px; right: -10px; width: 24px; height: 24px; font-size: 0.7rem;">
                                <i class="bi bi-exclamation-triangle"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
class OfficeManager {
    constructor() {
        this.quickView = document.getElementById('quickView');
        this.grid = document.getElementById('officeGrid');
        this.emptyState = document.getElementById('emptyState');
        this.graphInterval = null;
        this.graphPoints = Array(21).fill(50);
        
        // Filtering State
        this.searchTerm = '';
        this.activeFilter = 'all';
        this.activeSort = 'name-asc';
        
        this.init();
    }

    init() {
        this.setupSearch();
        this.setupToggles();
        this.setupFilters();
        this.setupSorting();
        this.setupRefresh();
        this.entranceAnimation();
    }

    setupToggles() {
        const gridBtn = document.getElementById('gridToggle');
        const listBtn = document.getElementById('listToggle');

        gridBtn.addEventListener('click', () => {
            this.grid.classList.remove('list-view');
            gridBtn.classList.add('active');
            listBtn.classList.remove('active');
            this.entranceAnimation();
        });

        listBtn.addEventListener('click', () => {
            this.grid.classList.add('list-view');
            listBtn.classList.add('active');
            gridBtn.classList.remove('active');
            this.entranceAnimation();
        });
    }

    setupFilters() {
        const chips = document.querySelectorAll('.filter-chip');
        chips.forEach(chip => {
            chip.addEventListener('click', () => {
                chips.forEach(c => c.classList.remove('active'));
                chip.classList.add('active');
                this.activeFilter = chip.dataset.filter;
                this.applyFilters();
            });
        });
    }

    setupSorting() {
        const sortSelect = document.getElementById('officeSort');
        sortSelect.addEventListener('change', (e) => {
            this.activeSort = e.target.value;
            this.sortCards();
        });
    }

    setupRefresh() {
        const refreshBtn = document.getElementById('refreshBtn');
        refreshBtn.addEventListener('click', () => {
            refreshBtn.classList.add('spinning');
            // Simulate a data reload
            setTimeout(() => {
                location.reload();
            }, 800);
        });
    }

    entranceAnimation() {
        const visibleCards = Array.from(document.querySelectorAll('.office-card')).filter(c => c.style.display !== 'none');
        gsap.from(visibleCards, {
            y: 40,
            opacity: 0,
            scale: 0.9,
            duration: 0.6,
            stagger: 0.08,
            ease: "back.out(1.7)",
            clearProps: "all"
        });
    }

    setupSearch() {
        const searchInput = document.getElementById('officeSearch');
        searchInput.addEventListener('input', (e) => {
            this.searchTerm = e.target.value.toLowerCase();
            this.applyFilters();
        });
    }

    applyFilters() {
        const cards = Array.from(document.querySelectorAll('.office-card'));
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const category = card.dataset.category;
            
            const matchesSearch = this.searchTerm === '' || name.includes(this.searchTerm);
            const matchesFilter = this.activeFilter === 'all' || category === this.activeFilter;

            if (matchesSearch && matchesFilter) {
                card.style.display = 'flex';
                visibleCount++;
                if (this.searchTerm !== '' && name.includes(this.searchTerm)) {
                    card.classList.add('highlight');
                } else {
                    card.classList.remove('highlight');
                }
            } else {
                card.style.display = 'none';
            }
        });

        this.emptyState.style.display = visibleCount === 0 ? 'block' : 'none';
        
        if (visibleCount > 0) {
            this.sortCards(); // Keep sort order when filtering
        }
    }

    sortCards() {
        const cards = Array.from(document.querySelectorAll('.office-card'));
        const container = this.grid;
        
        cards.sort((a, b) => {
            const nameA = a.dataset.name.toLowerCase();
            const nameB = b.dataset.name.toLowerCase();
            const assetsA = parseInt(a.dataset.count);
            const assetsB = parseInt(b.dataset.count);

            switch(this.activeSort) {
                case 'name-asc': return nameA.localeCompare(nameB);
                case 'name-desc': return nameB.localeCompare(nameA);
                case 'assets-desc': return assetsB - assetsA;
                case 'assets-asc': return assetsA - assetsB;
                default: return 0;
            }
        });

        // Re-append in new order
        cards.forEach(card => container.appendChild(card));
        this.entranceAnimation();
    }

    showQuickView(name, count, category) {
        document.getElementById('qvTitle').innerText = name;
        document.getElementById('qvCategory').innerText = category.toUpperCase();
        document.getElementById('qvAssets').innerText = count;
        
        // Reset placeholders
        document.getElementById('qvHead').innerText = 'Loading...';
        document.getElementById('qvContact').innerText = 'Loading...';
        document.getElementById('qvEmail').innerText = 'Loading...';
        document.getElementById('qvAlertsList').innerHTML = '<div class="text-center py-2"><i class="bi bi-arrow-clockwise animate-spin"></i>Loading...</div>';
        document.getElementById('qvEquipmentList').innerHTML = '<div class="text-center py-3"><i class="bi bi-arrow-clockwise animate-spin"></i>Loading...</div>';

        // Fetch details via AJAX
        fetch(`api/office_details.php?name=${encodeURIComponent(name)}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('qvHead').innerText = data.profile.head_of_office || 'Not Set';
                document.getElementById('qvContact').innerText = data.profile.contact_number || 'Not Set';
                document.getElementById('qvEmail').innerText = data.profile.office_email || 'Not Set';

                // Display Alerts
                const alertsList = document.getElementById('qvAlertsList');
                if (data.reports && data.reports.length > 0) {
                    alertsList.innerHTML = data.reports.map(report => `
                        <div class="mb-2 p-2" style="background: rgba(239, 68, 68, 0.05); border-radius: 8px; border-left: 2px solid #ef4444;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-white small fw-bold">${report.item_name}</span>
                                <span class="badge bg-danger" style="font-size: 0.6rem;">${report.urgency.toUpperCase()}</span>
                            </div>
                            <div class="text-white-50" style="font-size: 0.75rem;">${report.problem_description}</div>
                            <div class="mt-1 d-flex justify-content-between" style="font-size: 0.65rem; color: #888;">
                                <span><i class="bi bi-person me-1"></i>${report.reported_by}</span>
                                <span>${new Date(report.reported_at).toLocaleDateString()}</span>
                            </div>
                        </div>
                    `).join('');
                } else {
                    alertsList.innerHTML = '<div class="text-center py-2 text-muted small">No active alerts</div>';
                }

                const list = document.getElementById('qvEquipmentList');
                let totalCount = 0;
                
                if (data.equipment && data.equipment.length > 0) {
                    const groupedEquipment = data.equipment.reduce((acc, eq) => {
                        const key = eq.item + '|' + eq.brand;
                        if (!acc[key]) acc[key] = { ...eq, count: 0 };
                        acc[key].count += 1;
                        return acc;
                    }, {});

                    list.innerHTML = Object.values(groupedEquipment).map(eq => {
                        totalCount += eq.count;
                        const specs = [eq.processor, eq.ram, eq.hdd, eq.ssd].filter(s => s && s !== 'N/A').join(' | ');
                        return `
                            <div class="mb-2 p-2" style="background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 2px solid var(--neon-blue);">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="text-white small fw-bold">${eq.item}</span>
                                    <span class="badge bg-dark border border-secondary">${eq.count}</span>
                                </div>
                                <div style="font-size: 0.65rem; color: #888;">${specs || 'No specs listed'}</div>
                            </div>
                        `;
                    }).join('');
                } else {
                    list.innerHTML = '<div class="text-center py-3 text-muted">No records found.</div>';
                }

                document.getElementById('qvAssets').innerText = totalCount;
                const statusEl = document.getElementById('qvStatus');
                if (data.reports && data.reports.length > 0) {
                    statusEl.innerText = 'ALERT';
                    statusEl.className = 'qv-value text-danger';
                } else if (totalCount > 15) { 
                    statusEl.innerText = 'CRITICAL'; 
                    statusEl.className = 'qv-value text-danger'; 
                } else if (totalCount > 8) { 
                    statusEl.innerText = 'WARNING'; 
                    statusEl.className = 'qv-value text-warning'; 
                } else { 
                    statusEl.innerText = 'STABLE'; 
                    statusEl.className = 'qv-value text-success'; 
                }
            });

        document.getElementById('qvFullReport').onclick = () => {
            location.href = `report.php?search=${encodeURIComponent(name)}`;
        };

        document.getElementById('viewDevicesBtn').onclick = () => {
            const modalEl = document.getElementById('devicesModal');
            const modal = new bootstrap.Modal(modalEl);
            const sidebar = document.getElementById('tmSidebar');
            const main = document.getElementById('tmMain');
            
            sidebar.innerHTML = '<div class="text-center py-4"><i class="bi bi-arrow-clockwise animate-spin"></i></div>';
            main.innerHTML = '<div class="tm-no-data">Select a device to view performance</div>';
            
            modalEl.addEventListener('hidden.bs.modal', () => {
                if (this.graphInterval) clearInterval(this.graphInterval);
            }, { once: true });

            modal.show();

            fetch(`api/office_details.php?name=${encodeURIComponent(name)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.equipment && data.equipment.length > 0) {
                        sidebar.innerHTML = data.equipment.map((eq, index) => `
                            <div class="tm-item ${index === 0 ? 'active' : ''}" onclick="officeManager.showDeviceDetails(${JSON.stringify(eq).replace(/"/g, '&quot;')}, this)">
                                <div class="tm-item-icon">
                                    <i class="bi ${eq.item.toLowerCase().includes('laptop') ? 'bi-laptop' : 'bi-pc-display'} text-info"></i>
                                </div>
                                <div class="tm-item-info">
                                    <span class="tm-item-name">${eq.item}${eq.unit_no ? ' - Unit ' + eq.unit_no : ''}</span>
                                    <span class="tm-item-sub">${eq.brand}</span>
                                </div>
                            </div>
                        `).join('');
                        this.showDeviceDetails(data.equipment[0]);
                    } else {
                        sidebar.innerHTML = '<div class="text-center py-4 text-muted small">No devices</div>';
                    }
                });
        };

        this.quickView.classList.add('active');
    }

    showDeviceDetails(eq, element = null) {
        if (element) {
            document.querySelectorAll('.tm-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }

        const main = document.getElementById('tmMain');
        main.innerHTML = `
            <div class="tm-header">
                <div>
                    <span class="text-info text-uppercase small fw-bold tracking-wider">Device Performance</span>
                    <h2 class="text-white mb-0">${eq.item}${eq.unit_no ? ' - Unit ' + eq.unit_no : ''}</h2>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="tm-content-body">
                <div class="tm-comp-sidebar">
                    <div class="tm-comp-item active" onclick="officeManager.renderSummaryView(${JSON.stringify(eq).replace(/"/g, '&quot;')}, this)">
                        <div class="small fw-bold text-white">Summary</div>
                        <div class="text-muted" style="font-size: 0.65rem;">System Overview</div>
                    </div>
                    <div class="tm-comp-item" onclick="officeManager.renderComponentView('CPU', '${eq.processor}', 'Base speed: 3.40 GHz', this)">
                        <div class="small fw-bold text-white">CPU</div>
                        <div class="text-muted" style="font-size: 0.65rem;">15% 3.20 GHz</div>
                    </div>
                    <div class="tm-comp-item" onclick="officeManager.renderComponentView('Memory', '${eq.ram}', 'Form factor: DIMM', this)">
                        <div class="small fw-bold text-white">Memory</div>
                        <div class="text-muted" style="font-size: 0.65rem;">4.2/8.0 GB (52%)</div>
                    </div>
                    <div class="tm-comp-item" onclick="officeManager.renderComponentView('Storage', '${eq.hdd}', 'Type: ${eq.ssd !== 'N/A' ? 'Hybrid' : 'HDD'}', this)">
                        <div class="small fw-bold text-white">Disk</div>
                        <div class="text-muted" style="font-size: 0.65rem;">Active</div>
                    </div>
                </div>
                <div class="tm-details-area" id="tmDetailsArea"></div>
            </div>
        `;
        this.renderSummaryView(eq);
    }

    renderSummaryView(eq, element = null) {
        if (element) {
            document.querySelectorAll('.tm-comp-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }
        const area = document.getElementById('tmDetailsArea');
        area.innerHTML = `
            <div class="detail-grid">
                <div class="detail-card">
                    <span>Processor</span>
                    <strong>${eq.processor || 'N/A'}</strong>
                </div>
                <div class="detail-card">
                    <span>Memory</span>
                    <strong>${eq.ram || 'N/A'}</strong>
                </div>
                <div class="detail-card">
                    <span>Brand</span>
                    <strong>${eq.brand || 'N/A'}</strong>
                </div>
                <div class="detail-card">
                    <span>Storage</span>
                    <strong>${eq.ssd !== 'N/A' ? eq.ssd : eq.hdd || 'N/A'}</strong>
                </div>
            </div>
            <div class="tm-graph-container">
                <div class="d-flex justify-content-between align-items-center mb-4 px-4">
                    <div>
                        <div class="text-uppercase text-muted small">System Integrity Check</div>
                        <div class="text-white fw-bold" style="font-size: 1rem;">Performance Health</div>
                    </div>
                    <span class="badge rounded-pill text-dark" style="background: #10b981;">STABLE</span>
                </div>
                <div class="progress" style="height: 8px; background: rgba(255,255,255,0.08); margin: 0 24px 24px; border-radius: 999px;">
                    <div class="progress-bar bg-info" style="width: 92%; border-radius: 999px;"></div>
                </div>
                <div style="position:absolute; inset:0; pointer-events:none; background: linear-gradient(180deg, rgba(255,255,255,0.08), transparent);"></div>
            </div>
            <div class="detail-grid">
                <div class="detail-card">
                    <span>HDD</span>
                    <strong>${eq.hdd || 'N/A'}</strong>
                </div>
                <div class="detail-card">
                    <span>SSD</span>
                    <strong>${eq.ssd || 'N/A'}</strong>
                </div>
            </div>
        `;
    }

    renderComponentView(label, value, sub, element = null) {
        if (element) {
            document.querySelectorAll('.tm-comp-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
        }
        if (this.graphInterval) clearInterval(this.graphInterval);
        const area = document.getElementById('tmDetailsArea');
        area.innerHTML = `
            <div class="d-flex justify-content-between align-items-end mb-4">
                <div>
                    <h3 class="mb-1 text-white fw-bold">${label}</h3>
                    <div class="text-muted small">${sub}</div>
                </div>
                <span class="text-info small">${value}</span>
            </div>
            <div class="tm-graph-container">
                <svg viewBox="0 0 100 100" preserveAspectRatio="none" style="width: 100%; height: 100%; position: relative; z-index: 1;">
                    <path id="tmGraphPath" d="" fill="rgba(0, 242, 255, 0.12)" stroke="var(--neon-blue)" stroke-width="1" />
                </svg>
                <div style="position:absolute; inset:0; background: linear-gradient(180deg, rgba(0,0,0,0.12), transparent 35%); pointer-events:none;"></div>
            </div>
            <div class="detail-grid">
                <div class="detail-card">
                    <span>Utilization</span>
                    <strong id="tmStatUtil">--%</strong>
                </div>
                <div class="detail-card">
                    <span>Status</span>
                    <strong class="text-success">Optimal</strong>
                </div>
                <div class="detail-card" style="grid-column: span 2;">
                    <span>Info</span>
                    <strong style="font-size: 0.95rem;">${sub}</strong>
                </div>
            </div>
        `;
        this.startGraphAnimation();
    }

    startGraphAnimation() {
        const path = document.getElementById('tmGraphPath');
        const utilVal = document.getElementById('tmStatUtil');
        this.graphPoints = Array(21).fill(80);
        this.graphInterval = setInterval(() => {
            const newVal = 20 + Math.random() * 60;
            this.graphPoints.shift();
            this.graphPoints.push(newVal);
            let d = `M 0,100 L 0,${this.graphPoints[0]} `;
            for (let i = 1; i < this.graphPoints.length; i++) d += `L ${i * 5},${this.graphPoints[i]} `;
            d += `L 100,100 Z`;
            if (path) path.setAttribute('d', d);
            if (utilVal) utilVal.innerText = `${Math.floor(100 - newVal)}%`;
        }, 1000);
    }
}

window.addEventListener('DOMContentLoaded', () => {
    window.officeManager = new OfficeManager();
});
</script>

</body>
</html>