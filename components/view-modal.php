<!-- ========================================================================= -->
<!-- GLASSMORPHISM VERTICAL TRANSACTION RECEIPT MODAL                          -->
<!-- ========================================================================= -->
<link rel="stylesheet" href="../components/views.css">

<div class="modal-overlay" id="detailsModal" style="display: none;">
    <div class="glass-receipt-card">
        <!-- Close button to dismiss modal -->
        <button type="button" class="glass-close-btn" id="closeModal" title="Close Receipt">&times;</button>
        
        <!-- Receipt Header Branding & Status Badge -->
        <div class="receipt-header">
            <div class="receipt-logo">ACTIVE PICKLELABS</div>
            <p class="receipt-subtitle">Official Booking Receipt</p>
            <div class="receipt-status-pill" id="modalStatusPill">Confirmed</div>
        </div>

        <div class="receipt-divider"></div>

        <!-- Receipt Body Details & Rows -->
        <div class="receipt-body">
            <div class="receipt-row">
                <span class="label">Customer Name</span>
                <span class="value" id="modalCustomer">--</span>
            </div>
            <div class="receipt-row">
                <span class="label">Court</span>
                <span class="value" id="modalCourt">--</span>
            </div>
            <div class="receipt-row">
                <span class="label">Booking Type</span>
                <span class="value" id="modalType">--</span>
            </div>
            <div class="receipt-row">
                <span class="label">Reserved Date</span>
                <span class="value" id="modalDate">--</span>
            </div>
            <div class="receipt-row">
                <span class="label">Time Slot</span>
                <span class="value" id="modalTime">--</span>
            </div>
            <div class="receipt-row">
                <span class="label">Additional Info:</span>
                <span class="value" id="modalInfo">--</span>
            </div>

            <div class="receipt-divider dashed"></div>

            <!-- Total Pricing Row -->
            <div class="receipt-row total-row">
                <span class="label">Total Price</span>
                <span class="value amount" id="modalAmount">₱0.00</span>
            </div>
        </div>

        <!-- Receipt Footer Actions -->
        <div class="receipt-footer">
            <div class="modal-actions" style="justify-content: center;">
                <button type="button" class="btn-back-receipt" id="backModalBtn" style="width: 100%;">Back</button>
            </div>
        </div>
    </div>
</div>