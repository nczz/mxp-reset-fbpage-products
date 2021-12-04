jQuery(document).ready(function($) {
    $('#mxp_go_get_products_btn').click(function() {
        if (MXP_RFPS.catalog_id == '' || MXP_RFPS.access_token == '') {
            alert('請先完成 Facebook for WooCommerce 外掛的粉絲頁授權設定');
            return;
        }
        var data = {
            'action': 'mxp_ajax_get_fbpage_products_action',
            'data': {
                catalog_id: MXP_RFPS.catalog_id,
                access_token: MXP_RFPS.access_token,
                nonce: MXP_RFPS.nonce,
            },
        };
        var get_btn = $(this);
        get_btn.prop('disabled', true);
        $.post(MXP_RFPS.ajaxurl, data, function(res) {
            console.log(res);
            if (res.success) {
                console.log(res.data);
                var html = '<table id="mxp_fb_page_products"><thead><tr><th>功能</th><th>ID</th><th>Name</th><th>Retailer ID</th></tr></thead><tbody>';
                for (var i = 0; i < res.data.length; i++) {
                    html += '<tr id="' + res.data[i].id + '_tr"><td><button type="button" class="rfps_del" id="' + res.data[i].id + '">刪除</button></td><td>' + res.data[i].id + '</td><td>' + res.data[i].name + '</td><td>' + res.data[i].retailer_id + '</td></tr>';
                }
                html += '</tbody></table>';
                var btn = '第二步驟：<button type="button" id="mxp_go_del_products_btn">刪除全部粉絲頁商品</button><br>';
                $('#show_items').html(btn + html);
                $('#mxp_go_del_products_btn').click(function() {
                    $(this).prop('disabled', true);
                    $('.rfps_del').trigger('click');
                });
                $('.rfps_del').click(function() {
                    var self = $(this);
                    var id = self.attr('id');
                    self.prop('disabled', true);
                    var data = {
                        'action': 'mxp_ajax_del_fbpage_products_action',
                        'data': {
                            id: id,
                            access_token: MXP_RFPS.access_token,
                            nonce: MXP_RFPS.nonce,
                        },
                    };
                    $(document).queue(function(next) {
                        $.post(MXP_RFPS.ajaxurl, data, function(res) {
                            console.log(res);
                            if (res.success) {
                                $('#' + id + '_tr').css('text-decoration', 'line-through'); //刪除線
                            } else {
                                self.prop('disabled', false); //出問題，解開按鈕繼續
                            }
                            next();
                        });
                    });
                });
            } else {
                alert(res.data.msg);
                get_btn.prop('disabled', false);
            }
        });
    });
});