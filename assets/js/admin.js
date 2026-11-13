
document.addEventListener('DOMContentLoaded', () => {
    // تأكيد عمليات الحذف
    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message = element.dataset.confirm || 'هل أنت متأكد من تنفيذ هذه العملية؟';

            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // معاينة صور المنتجات قبل رفعها
    document.querySelectorAll('[data-image-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const preview = document.querySelector(input.dataset.imageInput);

            if (!preview || !input.files || !input.files[0]) {
                return;
            }

            const file = input.files[0];

            if (!file.type.startsWith('image/')) {
                alert('يرجى اختيار ملف صورة صالح.');
                input.value = '';
                return;
            }

            const reader = new FileReader();

            reader.onload = () => {
                preview.src = reader.result;
                preview.hidden = false;
            };

            reader.readAsDataURL(file);
        });
    });

    // إخفاء رسائل التنبيه
    document.querySelectorAll('[data-auto-dismiss]').forEach((alert) => {
        const delay = Number(alert.dataset.autoDismiss) || 5000;

        window.setTimeout(() => {
            alert.style.transition = 'opacity .3s ease';
            alert.style.opacity = '0';

            window.setTimeout(() => alert.remove(), 300);
        }, delay);
    });
});
