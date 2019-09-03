$( function() {
    var whales = ['asd', 'qwe', 'rty', 'sdf', 'wer', 'asdfew'];
        //        var whales = [];
        var index = 0;

        var input = $("#a1");
        input.autocomplete({
            source: whales,
            open: function(event, ui) {
            input.autocomplete("widget").css({
                "width": input.css('width')
            });
        },
           minLength: 0
        }).focus(function () {
            input.val('');
            if (this.value == "") {
//            console.log(this.value + "VALUE");
                input.autocomplete("search");

            }
        });
}
);

