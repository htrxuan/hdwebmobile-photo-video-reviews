document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('hdpvr_media');
    if (!input) {
        return;
    }

    var form = input.closest('form');
    if (form) {
        form.setAttribute('enctype', 'multipart/form-data');
    }

    var hint = document.createElement('span');
    hint.className = 'hdpvr-selected-count description';
    input.insertAdjacentElement('afterend', hint);

    input.addEventListener('change', function () {
        var max = parseInt(input.dataset.maxFiles, 10) || input.files.length;
        if (input.files.length > max) {
            hint.textContent = ' Only the first ' + max + ' file(s) will be used.';
        } else if (input.files.length > 0) {
            hint.textContent = ' ' + input.files.length + ' file(s) selected.';
        } else {
            hint.textContent = '';
        }
    });
});
