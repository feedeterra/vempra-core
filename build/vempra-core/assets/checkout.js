/* checkout.js — snippets 60 (cupon en el resumen) y 64 (boton "Ir a pagar"). Carga solo en el checkout; necesita jQuery. */

/* ===== snippet 60 ===== */
jQuery(function($){
        function vempraToast(ok, msg){
            $('#vempra-toast').remove();
            var bg = ok ? '#1e7e34' : '#b02a37';
            var icon = ok ? '✓' : '✕';
            var $t = $('<div id="vempra-toast" style="position:fixed;top:24px;left:50%;transform:translateX(-50%);z-index:99999;background:'+bg+';color:#fff;padding:14px 22px;border-radius:8px;box-shadow:0 6px 24px rgba(0,0,0,.25);font-weight:600;max-width:90%;text-align:center;">'+icon+' '+msg+'</div>');
            $('body').append($t);
            setTimeout(function(){ $t.fadeOut(400, function(){ $(this).remove(); }); }, 4000);
        }
        $(document).on('click', '#vempra_apply_coupon', function(e){
            e.preventDefault();
            var $btn = $(this), code = $.trim($('#vempra_coupon_code').val());
            if(!code) return;
            $btn.prop('disabled', true);
            $.ajax({
                type: 'POST',
                url: wc_checkout_params.wc_ajax_url.toString().replace('%%endpoint%%','apply_coupon'),
                data: { security: wc_checkout_params.apply_coupon_nonce, coupon_code: code },
                success: function(response){
                    if(response && response.toString().indexOf('woocommerce-error') === -1){
                        vempraToast(true, '¡Listo! Se aplicó tu cupón y ya tenés tu descuento.');
                    } else {
                        vempraToast(false, 'Ese cupón no es válido o no se pudo aplicar.');
                    }
                    $('body').trigger('update_checkout');
                    $('#vempra_coupon_code').val('');
                },
                error: function(){ vempraToast(false, 'No se pudo aplicar el cupón. Intentá de nuevo.'); },
                complete: function(){ $btn.prop('disabled', false); }
            });
        });
        $(document).on('keypress', '#vempra_coupon_code', function(e){
            if(e.which === 13){ e.preventDefault(); $('#vempra_apply_coupon').trigger('click'); }
        });
    });

/* ===== snippet 64 ===== */
jQuery(function($){
    $(document).on('click','#vempra-ir-pago',function(e){
      e.preventDefault();
      var t=document.querySelector('#payment');
      if(t) t.scrollIntoView({behavior:'smooth',block:'start'});
    });
  });
