$(document).ready(function () {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    if ($("#success").val() == 'yes') {
        alert('Your alert was created successfully!');
        location = "/alerts";
    }
    $(".alert-delete").click(function () {
        var id = {
            'id': $(this).prop('id').replace('alert', '')
        };
        $.post("ajax/delete-alert", id);
        location.reload();
    });
});