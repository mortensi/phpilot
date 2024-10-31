<footer class="footer">
    <div class="columns">
        <div class="column is-hidden-mobile"></div>
        <div class="column is-two-fifths is-full-mobile">
            <form class="search-form mt-4 p-2 pl-4" method="post" action="">
                <div class="field has-addons">
                    <div class="control is-expanded is-flex is-vcentered">
                        <input name="q" autocomplete="off" class="input " type="text" placeholder="Ask the jPilot a question...">
                    </div>
                    <div class="control has-icons-left">
                        <button id="chat" disabled type="submit" class="button search-button" autocomplete="off">
                            <svg class="overflow-visible" width="18" height="19" viewBox="0 0 18 19" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M14.25 7.72059C14.25 11.591 11.2076 14.6912 7.5 14.6912C3.79242 14.6912 0.75 11.591 0.75 7.72059C0.75 3.8502 3.79242 0.75 7.5 0.75C11.2076 0.75 14.25 3.8502 14.25 7.72059Z" stroke-width="1.5"/>
                                <path d="M12 12.3529L17 17.5" stroke-width="1.5"/>
                                </svg>
                        </button>
                    </div>
                </div>
            </form>
            <a id="reset" class="pl-4" href="#">restart the conversation</a>
            
            
            <script>
                $("input").on("change keyup blur input reset", function() {
                    if (this.value.length){
                        $("#chat").prop("disabled", false)
                    }
                    else {
                        $("#chat").prop("disabled", true);
                    }
                });
            
                $("#reset").click(function(e){
                    e.preventDefault();
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: "{{ url('/reset') }}",
                        processData: true,
						headers: {
							'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') // Add CSRF token here
						},
                        success: function(data) {
                            $(".bubble-right, .bubble-left").fadeOut("slow", function() {
                                $("#conversation").empty();
                            });
                        },
                        error: function(xhr, status, error) {
                            // Log the error details to the console
                            console.log("Error occurred:", error);
                            console.log("Status:", status);
                            console.log("Response:", xhr.responseText);
                            
                            // Optionally, you could display the error to the user
                            alert("An error occurred: " + error);
                        }
                    });
                });
            </script>
        </div>
        <div class="column is-hidden-mobile"></div>
    </div>
</footer>
<script>
    function bubbles_cb(){
        $("input").val('');
        $(".button").prop("disabled", true);
    }
    
    bubbles("{{ url('/chat') }}", /*[[${conversation}]]*/ [], bubbles_cb);
</script>