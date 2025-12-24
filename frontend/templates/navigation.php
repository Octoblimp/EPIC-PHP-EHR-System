<?php
/**
 * Navigation Sidebar Component
 */
$currentActivity = $currentActivity ?? 'summary';
?>
<nav class="nav-sidebar">
    <div class="nav-item <?php echo $currentActivity === 'summary' ? 'active' : ''; ?>" data-activity="summary">
        <span class="icon">📊</span>
        Summary
    </div>
    <div class="nav-item <?php echo $currentActivity === 'flowsheets' ? 'active' : ''; ?>" data-activity="flowsheets">
        <span class="icon">📋</span>
        Flowsheets
    </div>
    <div class="nav-item" data-activity="intake-output">
        <span class="icon">💧</span>
        Intake/Output
    </div>
    <div class="nav-item <?php echo $currentActivity === 'notes' ? 'active' : ''; ?>" data-activity="notes">
        <span class="icon">📝</span>
        Notes
    </div>
    <div class="nav-item <?php echo $currentActivity === 'results' ? 'active' : ''; ?>" data-activity="results">
        <span class="icon">🔬</span>
        Results Rev...
    </div>
    <div class="nav-item" data-activity="chart-review">
        <span class="icon">📁</span>
        Chart Review
    </div>
    <div class="nav-item" data-activity="history">
        <span class="icon">📜</span>
        History
    </div>
    
    <div class="nav-section-header">Orders</div>
    <div class="nav-item <?php echo $currentActivity === 'orders' ? 'active' : ''; ?>" data-activity="orders">
        <span class="icon">📦</span>
        Manage Orders
    </div>
    <div class="nav-item" data-activity="care-plan">
        <span class="icon">🎯</span>
        Care Plan
    </div>
    
    <div class="nav-section-header">Clinical</div>
    <div class="nav-item" data-activity="education">
        <span class="icon">📚</span>
        Education
    </div>
    <div class="nav-item" data-activity="demographics">
        <span class="icon">👤</span>
        Demographics
    </div>
    <div class="nav-item" data-activity="snapshot">
        <span class="icon">📷</span>
        SnapShot
    </div>
    
    <div class="nav-section-header">Workflows</div>
    <div class="nav-item" data-activity="review-flows">
        <span class="icon">🔄</span>
        Review Flows...
    </div>
    <div class="nav-item" data-activity="order-review">
        <span class="icon">✅</span>
        Order Review
    </div>
    
    <div class="nav-item" data-activity="more" style="margin-top: auto;">
        <span class="icon">➕</span>
        More ►
    </div>
</nav>
