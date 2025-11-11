/**
 * Vietnam Banking Data Hub - Frontend Scripts
 * JavaScript cho shortcodes và widgets
 */

(function($) {
    'use strict';

    const VBDH_Frontend = {
        /**
         * Initialize
         */
        init: function() {
            this.initCalculator();
            this.initTableSorting();
            this.initResponsiveTables();
        },

        /**
         * Initialize Calculator
         */
        initCalculator: function() {
            const self = this;

            // Handle calculator submission
            $(document).on('click', '#calc-submit', function(e) {
                e.preventDefault();
                self.calculateInterest();
            });

            // Allow Enter key to submit
            $(document).on('keypress', '.calc-input', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    self.calculateInterest();
                }
            });

            // Format amount input with thousands separator
            $(document).on('input', '#calc-amount', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (value) {
                    $(this).val(value);
                }
            });
        },

        /**
         * Calculate Interest via AJAX
         */
        calculateInterest: function() {
            const amount = parseFloat($('#calc-amount').val());
            const term = parseInt($('#calc-term').val());
            const bankId = $('#calc-bank').val();

            // Validation
            if (!amount || amount < 1000000) {
                this.showCalculatorError('Vui lòng nhập số tiền tối thiểu 1,000,000 VNĐ');
                return;
            }

            if (!bankId) {
                this.showCalculatorError('Vui lòng chọn ngân hàng');
                return;
            }

            // Show loading
            const $button = $('#calc-submit');
            const originalText = $button.text();
            $button.prop('disabled', true).text('Đang tính...');

            // Hide previous results
            $('#calc-result').hide();

            // AJAX request
            $.ajax({
                url: vbdhFrontend.ajaxurl,
                type: 'POST',
                data: {
                    action: 'vbdh_calculate_interest',
                    nonce: vbdhFrontend.nonce,
                    amount: amount,
                    term: term,
                    bank_id: bankId
                },
                success: function(response) {
                    if (response.success) {
                        // Display results
                        $('#result-rate').text(response.data.rate + '%');
                        $('#result-interest').text(
                            new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            }).format(response.data.interest)
                        );
                        $('#result-total').text(
                            new Intl.NumberFormat('vi-VN', {
                                style: 'currency',
                                currency: 'VND'
                            }).format(response.data.total)
                        );

                        // Show result box with animation
                        $('#calc-result').slideDown(300);
                    } else {
                        VBDH_Frontend.showCalculatorError(
                            response.data.message || 'Có lỗi xảy ra khi tính toán'
                        );
                    }
                },
                error: function(xhr, status, error) {
                    VBDH_Frontend.showCalculatorError('Lỗi kết nối: ' + error);
                },
                complete: function() {
                    $button.prop('disabled', false).text(originalText);
                }
            });
        },

        /**
         * Show Calculator Error
         */
        showCalculatorError: function(message) {
            // Remove existing error
            $('.calc-error').remove();

            // Create error message
            const $error = $('<div class="calc-error" style="' +
                'margin: 15px 0; ' +
                'padding: 12px 15px; ' +
                'background: #fbe8e8; ' +
                'border-left: 4px solid #d63638; ' +
                'color: #d63638; ' +
                'border-radius: 4px;">' +
                message +
                '</div>');

            // Insert after form
            $('.vbdh-calculator-form').after($error);

            // Auto remove after 5 seconds
            setTimeout(function() {
                $error.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * Initialize Table Sorting
         */
        initTableSorting: function() {
            const self = this;

            $('.vbdh-comparison-shortcode-table th.sortable').on('click', function() {
                const $th = $(this);
                const $table = $th.closest('table');
                const columnIndex = $th.index();
                const currentOrder = $th.data('order') || 'asc';
                const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';

                // Remove sorting indicators from other columns
                $table.find('th.sortable').removeClass('sorted-asc sorted-desc').data('order', '');

                // Add sorting indicator to current column
                $th.addClass('sorted-' + newOrder).data('order', newOrder);

                // Sort the table
                self.sortTable($table, columnIndex, newOrder);
            });
        },

        /**
         * Sort Table
         */
        sortTable: function($table, columnIndex, order) {
            const $tbody = $table.find('tbody');
            const $rows = $tbody.find('tr').toArray();

            $rows.sort(function(a, b) {
                const aValue = $(a).find('td').eq(columnIndex).text().trim();
                const bValue = $(b).find('td').eq(columnIndex).text().trim();

                // Try to parse as number
                const aNum = parseFloat(aValue.replace(/[^\d.-]/g, ''));
                const bNum = parseFloat(bValue.replace(/[^\d.-]/g, ''));

                let result = 0;

                if (!isNaN(aNum) && !isNaN(bNum)) {
                    // Numeric comparison
                    result = aNum - bNum;
                } else {
                    // String comparison
                    result = aValue.localeCompare(bValue, 'vi');
                }

                return order === 'asc' ? result : -result;
            });

            // Re-append rows in new order
            $tbody.empty().append($rows);

            // Re-apply row highlighting
            $tbody.find('tr').removeClass('highest-rate');
            if (columnIndex === $table.find('th.sortable[data-sort="rate"]').index()) {
                $tbody.find('tr').first().addClass('highest-rate');
            }
        },

        /**
         * Initialize Responsive Tables
         */
        initResponsiveTables: function() {
            // Add wrapper for horizontal scrolling on mobile
            $('.vbdh-comparison-shortcode-table, .vbdh-exchange-shortcode-table').each(function() {
                if (!$(this).parent().hasClass('table-responsive-wrapper')) {
                    $(this).wrap('<div class="table-responsive-wrapper" style="overflow-x: auto;"></div>');
                }
            });
        },

        /**
         * Format Currency
         */
        formatCurrency: function(number) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(number);
        },

        /**
         * Format Percentage
         */
        formatPercent: function(number, decimals = 2) {
            return number.toFixed(decimals) + '%';
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        VBDH_Frontend.init();
    });

    // Expose to global scope for external use
    window.VBDH_Frontend = VBDH_Frontend;

})(jQuery);
