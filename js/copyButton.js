document.addEventListener('DOMContentLoaded', function () {
    var copyBtn = document.getElementById('copyButton');
    if (copyBtn) {
        copyBtn.addEventListener('click', function () {
            var url = copyBtn.getAttribute('data-url');
            navigator.clipboard.writeText(url).then(function () {
                alert('Copied to clipboard!');
            }).catch(function (err) {
                console.error('Error while copying to clipboard: ', err);
            });
        });
    }
});