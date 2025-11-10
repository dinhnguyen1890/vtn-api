/**
 * Vietnam Banking Data Hub - Admin Scripts
 */

(function($) {
    'use strict';

    const VBDH = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Sync bank button
            $(document).on('click', '.vbdh-sync-bank', this.syncBank);

            // Delete bank button
            $(document).on('click', '.vbdh-delete-bank', this.deleteBank);

            // Auto-refresh logs (optional)
            if ($('.vbdh-logs-tab').length) {
                // Uncomment to enable auto-refresh every 30 seconds
                // setInterval(function() {
                //     location.reload();
                // }, 30000);
            }
        },

        /**
         * Sync bank via AJAX
         */
        syncBank: function(e) {
            e.preventDefault();

            const $button = $(this);
            const bankId = $button.data('bank-id');
            const bankName = $button.data('bank-name');

            if (!confirm(vbdhAdmin.strings.syncing + ' ' + bankName + '?')) {
                return;
            }

            // Disable button
            $button.prop('disabled', true).text(vbdhAdmin.strings.syncing);

            // AJAX request
            $.ajax({
                url: vbdhAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vbdh_sync_bank',
                    nonce: vbdhAdmin.nonce,
                    bank_id: bankId,
                    type: 'both'
                },
                success: function(response) {
                    if (response.success) {
                        VBDH.showNotice('success', vbdhAdmin.strings.success);

                        // Log result
                        if (response.data) {
                            console.log('Sync result:', response.data);
                        }

                        // Reload page after 1 second
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        VBDH.showNotice('error', response.data.message || vbdhAdmin.strings.error);
                        $button.prop('disabled', false).text('Sync');
                    }
                },
                error: function(xhr, status, error) {
                    VBDH.showNotice('error', vbdhAdmin.strings.error + ': ' + error);
                    $button.prop('disabled', false).text('Sync');
                }
            });
        },

        /**
         * Delete bank via AJAX
         */
        deleteBank: function(e) {
            e.preventDefault();

            const $button = $(this);
            const bankId = $button.data('bank-id');
            const bankName = $button.data('bank-name');

            if (!confirm(vbdhAdmin.strings.confirm_delete + '\n\n' + bankName)) {
                return;
            }

            // Disable button
            $button.prop('disabled', true);

            // AJAX request
            $.ajax({
                url: vbdhAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vbdh_ajax_delete_bank',
                    nonce: vbdhAdmin.nonce,
                    bank_id: bankId
                },
                success: function(response) {
                    if (response.success) {
                        VBDH.showNotice('success', vbdhAdmin.strings.success);

                        // Remove row from table
                        $button.closest('tr').fadeOut(300, function() {
                            $(this).remove();

                            // Check if table is empty
                            if ($('tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    } else {
                        VBDH.showNotice('error', response.data.message || vbdhAdmin.strings.error);
                        $button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    VBDH.showNotice('error', vbdhAdmin.strings.error + ': ' + error);
                    $button.prop('disabled', false);
                }
            });
        },

        /**
         * Show notice
         */
        showNotice: function(type, message) {
            // Remove existing notices
            $('.vbdh-notice').remove();

            // Create notice
            const $notice = $('<div class="vbdh-notice ' + type + '">' + message + '</div>');

            // Insert notice
            $('.vbdh-tab-content').prepend($notice);

            // Auto-remove after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);

            // Scroll to top
            $('html, body').animate({
                scrollTop: $('.vbdh-tab-content').offset().top - 50
            }, 300);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VBDH.init();
    });

})(jQuery);
