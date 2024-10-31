function scroll(){
    $("html, body").animate({ scrollTop: $(document).height() }, 2000);
    return false;
}


$(document).ready(function() {
    $('input:first').focus();

    $('textarea').each(function() {
        console.log(this.scrollHeight);
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});

