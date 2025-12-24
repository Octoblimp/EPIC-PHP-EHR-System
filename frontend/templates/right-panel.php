<?php
/**
 * Right Side Panel - Medications, Orders, Vitals
 */
?>
<aside class="right-panel">
    <!-- Vitals Widget -->
    <div class="panel-header">
        Vitals
        <button class="btn btn-primary" style="padding: 2px 8px; font-size: 10px;">+ Add</button>
    </div>
    <div id="vitals-widget">
        <!-- Loaded via JavaScript -->
        <div class="p-2 text-muted text-center">Loading vitals...</div>
    </div>

    <!-- Medications Panel -->
    <div class="panel-header" style="margin-top: 10px;">
        Medications
        <button class="btn btn-primary" style="padding: 2px 8px; font-size: 10px;">MAR</button>
    </div>
    <div id="medications-panel">
        <!-- Loaded via JavaScript -->
        <div class="p-2 text-muted text-center">Loading medications...</div>
    </div>

    <!-- Orders Panel -->
    <div class="panel-header" style="margin-top: 10px;">
        Orders
    </div>
    <div id="orders-panel">
        <!-- Loaded via JavaScript -->
        <div class="p-2 text-muted text-center">Loading orders...</div>
    </div>
</aside>
