<script>
    function fetchLink() {
        const categoryId = document.querySelector('#category_id')?.value;
        const chatId = document.querySelector('#chat_id')?.value;

        if (!categoryId || !chatId) {
            return toastr.error('{{ __('Category or Chat ID is missing.') }}')
        }

        fetch('/share/link', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    category_id: categoryId,
                    chat_id: chatId
                })
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP ${response.status}: Failed to generate share link`);
                return response.json();
            })
            .then(data => {
                if (data.link) {
                    document.querySelector('#result')?.setAttribute('value', data.link);
                    toastr.success('{{ __('Share link generated successfully') }}');
                } else {
                    toastr.error(data.message || '{{ __('Failed to generate share link') }}');
                }
            })
            .catch(error => {
                console.error('Share link generation failed:', error);
                toastr.error(error.message || '{{ __('Failed to generate share link. Please try again.') }}');
            });
    }

    function copyToClipboard($result) {
        const copyText = $result;

        if (copyText === undefined || copyText === null || copyText === "") {
            toastr.error('{{ __('Please generate link first') }}');
            return;
        }

        navigator.clipboard.writeText(copyText)
            .then(() => {
                toastr.success('{{ __('Link copied to clipboard') }}');
            })
            .catch(error => {
                console.error('Failed to copy link to clipboard:', error);
                toastr.error('{{ __('Failed to copy link. Please try again.') }}');
            });
    }
</script>
