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

            // Generate content button (daily/ranking)
            $(document).on('click', '.vbdh-generate-content', this.generateContent);

            // Generate comparison button
            $(document).on('click', '#generate-comparison-btn', this.generateComparison);

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
         * Generate content (daily_rates or ranking)
         */
        generateContent: function(e) {
            e.preventDefault();

            const $button = $(this);
            const contentType = $button.data('type');
            const originalText = $button.html();

            // Disable button
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> Đang tạo...');

            // AJAX request
            $.ajax({
                url: vbdhAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vbdh_generate_content',
                    nonce: vbdhAdmin.nonce,
                    content_type: contentType
                },
                success: function(response) {
                    if (response.success) {
                        VBDH.showNotice('success', response.data.message +
                            ' <a href="' + response.data.edit_url + '" target="_blank">Xem bài viết</a>');

                        // Reload after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        VBDH.showNotice('error', response.data.message || 'Có lỗi xảy ra');
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    VBDH.showNotice('error', 'Lỗi: ' + error);
                    $button.prop('disabled', false).html(originalText);
                }
            });
        },

        /**
         * Generate comparison post
         */
        generateComparison: function(e) {
            e.preventDefault();

            const bankId1 = $('#compare_bank_1').val();
            const bankId2 = $('#compare_bank_2').val();

            if (!bankId1 || !bankId2) {
                VBDH.showNotice('error', 'Vui lòng chọn 2 ngân hàng để so sánh');
                return;
            }

            const $button = $(this);
            const originalText = $button.html();

            // Disable button
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt spin"></span> Đang tạo...');

            // AJAX request
            $.ajax({
                url: vbdhAdmin.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vbdh_generate_comparison',
                    nonce: vbdhAdmin.nonce,
                    bank_id_1: bankId1,
                    bank_id_2: bankId2
                },
                success: function(response) {
                    if (response.success) {
                        VBDH.showNotice('success', response.data.message +
                            ' <a href="' + response.data.edit_url + '" target="_blank">Xem bài viết</a>');

                        // Reset selects and reload
                        $('#compare_bank_1, #compare_bank_2').val('');

                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        VBDH.showNotice('error', response.data.message || 'Có lỗi xảy ra');
                        $button.prop('disabled', false).html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    VBDH.showNotice('error', 'Lỗi: ' + error);
                    $button.prop('disabled', false).html(originalText);
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
