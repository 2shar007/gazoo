$(document).ready(function () {
    // Login form
    $('#login-btn').popover();

    // Ajaxify
    $(document).on('submit click', '.ajaxify' , function(event) {
        var $this = $(this), settings = {};
        if ($this.is('form'))
        {
            settings = {
                url: $this.attr('action'),
                type: $this.attr('method'),
                data: $this.serialize()
            };
        } else if ($this.is('a')) {
            settings = {
                url: $this.attr('href'),
                type: 'get'
            };
        } else
            return;
        event.preventDefault();

        $.ajax(settings).done(function (resp) {
            if (!resp.errno) {
                var call = $this.data('success');
                if (call)
                    callback[call](resp);
            } else {
                if ($this.is('form')) {
                    var $alert = $this.find('.alert');
                    if ($alert.length == 0)
                        $this.prepend(template_alert(resp.error));
                    else
                        $alert.find('span').text(resp.error);
                } else {
                    $('body .main-container').prepend(template_alert(resp.error));
                }
            }
        });
    });
});

var callback = {
    onloggin: function (resp) {
        window.location.reload();
    },
    onfollow: function (resp) {
        $('.follow-button[data-calendar-id="' + resp.id + '"]').html(resp.content);
		//alert('test');
		$.get($('#baseUrl').val() + 'push/' + resp.id);
    },
    onunfollow: function (resp) {
		//alert('untest');
        $('.follow-button[data-calendar-id="' + resp.id + '"]').html(resp.content);
		$.get($('#baseUrl').val() + 'unpush/' + resp.id);
    }
};

function template_alert(error)
{
    return '<div class="alert error"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Error!</strong> <span>' + (error ? error : 'Gratz, your universe will falling appart now !') + '</span></div>'
}
