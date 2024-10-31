var converter = new showdown.Converter();

function bubbles(endpoint, history, callback=undefined){

    // Ensure history is an array and use it
    if (Array.isArray(history)) {
    	history.forEach(function(value) {
            if (value.type == "UserMessage")
            	$( "#conversation" ).append('<div class="bubble bubble-right">' + value.content + '</div>');
        	if (value.type == "AiMessage")
            	$( "#conversation" ).append('<div class="bubble bubble-left">' + converter.makeHtml(value.content) + '</div>');
        });
    } else {
        console.error('Expected an array, but got:', conversation);
    }

    scroll();

    $("#chat").click(function(e){
        e.preventDefault();
        ttft = 0;
        now = Date.now();
        q = $("input").val()
        if (callback != undefined){callback();}
        $( "#conversation" ).append('<div class="bubble bubble-right">' + q + '</div>');
        bubble = $("<div>", {'class': "bubble bubble-left"}).appendTo("#conversation");
        dot = $("<div>", {'class': "dot-flashing"});
        dot.appendTo(bubble);
        scroll();
        var lastResponseLength = false;
        $.ajax({
            type: "POST",
            dataType: "text",
            contentType: "application/x-www-form-urlencoded; charset=UTF-8",
            url: endpoint,
            data: $.param({ q: q }),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Add CSRF token here
            },
            processData: false,
            xhrFields: {
                // Getting on progress streaming response
                onprogress: function(e)
                {
                    if (!ttft) {ttft = Date.now() - now;}
                    var progressResponse;
                    var response = e.currentTarget.response;
                    if(lastResponseLength === false)
                    {
                        progressResponse = response;
                        lastResponseLength = response.length;
                    }
                    else
                    {
                        progressResponse = response.substring(lastResponseLength);
                        lastResponseLength = response.length;
                    }
                    bubble.html(converter.makeHtml(response));
                    $(document).scrollTop($(document).height());
                }
            },
            success: function(data) {
                bubble.find('a').attr('target', '_blank');
                etfl = Date.now() - now;
                bubble.attr('data-etfl', etfl);
                bubble.attr('data-ttft', ttft);
                bubble.attr("title", `Time to first token is ${ttft}ms. Elapsed time first to last token is ${etfl}ms`);
            }
        });
        return false;
    });
}