<?= $this->extend('Layouts/default_2') ?>

<?= $this->section('head') ?>
<style>
    .question-center {
        width: 600px;
        max-width: 100%;
        margin: auto;
    }
    .stars-container {
        display: flex;
        justify-content: center;
        gap: 6px;
        font-size: 32px;
        color: #c0c0c0;
    }
    .notify.notify-autoclose::before {
        display: none;
    }
    .mcq-option-wrapper p {
        margin-bottom: 5px;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<?php
if (!$currentTopic) {
    ?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> <?= !empty($message) ? $message : "No topic for you. Talk to your teacher about this." ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
else if (count($questions) == 0) {
    ?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i> <?= !empty($message) ? $message : "No questions found in your current topic " . $currentTopic['name'] . ". Talk to your teacher to add questions in this topic." ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}
else {
    ?>
    <div class="container-fluid mt-5">
        <div class="row">
            <div class="col-12">
                <div class="question-center">
                    
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-center" style="gap: 10px;">
                                <span><?= $currentTopic['name'] ?></span>
                                <span class="elapsed-time-wrapper">Elapsed Time: <span id="elapsedTime"></span></span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">

                            <div id="questionsWrapper">

                            </div>

                            <div class="question-footer d-flex justify-content-end mt-3">
                                <button class="btn btn-primary" id="submitBtn">Submit</button>
                            </div>

                            <div class="question-solution mt-3" style="display: none;">
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">Solution</h5>
                                        <div class="solution-content"></div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-3">
                                    <button class="btn btn-primary next-question" id="nextQuestionBtn">Next</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>

<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script src="//cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.4/MathJax.js?config=TeX-AMS_HTML"></script>
<script>
    $(document).ready(async function() {

        let timeInterval = null;
        let currentQuestionIndex = -1;
        let questions = <?= json_encode($questions) ?>;

        await showNextQuestion();

        $("#submitBtn").click(async function() {
            
            let questionType = $("#questionType").val();
            let questionId = $("#questionId").val();
            let selectedAnswer = undefined;
            let showingSolution = false;

            if (questionType == 'mcq') {
                selectedAnswer = $('input[name="answer"]:checked').val();
            }
            else {
                selectedAnswer = $('.question-item').find('.text-answer').val();
            }

            if (selectedAnswer == undefined || selectedAnswer == '') {
                new Notify({
                    title: 'Warning',
                    text: 'Please select an answer',
                    status: 'warning',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            try {

                let formData = new FormData();
                formData.append('question_id', questionId);
                formData.append('answer', selectedAnswer);

                // Show loader in submit button
                $(this).attr('data-disabled', "true").attr('data-content', $(this).html()).html('<i class="fa fa-spinner fa-spin"></i>').css('pointer-events', 'none');

                const res = await ajaxCall({
                    url: baseUrl + 'submit-answer',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == 'success') {

                    if (res.is_correct) {
                        new Notify({
                            title: 'Info',
                            text: 'Correct Answer',
                            status: 'success',
                            autoclose: true,
                            autotimeout: 3000
                        });

                        await showNextQuestion();
                    }
                    else {
                        new Notify({
                            title: 'Info',
                            text: 'Incorrect Answer',
                            status: 'error',
                            autoclose: true,
                            autotimeout: 3000
                        });

                        if (res.solution == "") {
                            await showNextQuestion();
                        }
                        else {
                            $('.question-solution').show();
                            $('.question-solution .solution-content').html(res.solution);
                            showingSolution = true;

                            // Force MathJax to re-render the newly loaded content
                            MathJax.Hub.Queue([
                                "Typeset",
                                MathJax.Hub,
                                $('.question-solution .solution-content').get(0),
                            ]);
                        }
                    }
                }
                else {
                    new Notify({
                        title: 'Error',
                        text: res.message || 'Something went wrong',
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });

                    if (res.message == 'session_completed') {
                        window.location.reload();
                    }
                }
            }
            catch (err) {

                new Notify({
                    title: 'Error',
                    text: err.responseJSON.message || 'Something went wrong',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });

                if (err.status == 401) {
                    alert("You have been logged out.");
                    window.location.reload();
                }
            }

            // Reset submit button
            $('#submitBtn').html($(this).attr('data-content')).css('pointer-events', 'auto').removeAttr('data-disabled');

            if (showingSolution) {
                $('#submitBtn').css({
                    'pointer-events': 'none',
                    'opacity': 0.5
                }).attr('data-disabled', "true");
                
                $('.question-item').css({
                    'pointer-events': 'none',
                });
            }
        });

        $(document).on('keyup', '.text-answer', function() {
            // If Enter key is pressed
            if (event.keyCode == 13) {
                if ($('#submitBtn').attr('data-disabled') == "true") {
                    return;
                }
                $('#submitBtn').click();
            }
        });

        $('#nextQuestionBtn').click(async function() {
            $('#submitBtn').removeAttr('data-disabled').css({
                'pointer-events': 'auto',
                'opacity': 1
            });
            $(".question-solution").hide();
            await showNextQuestion();
        });

        async function showNextQuestion() {

            currentQuestionIndex++;

            if (currentQuestionIndex >= questions.length) {

                clearInterval(timeInterval);
                timeInterval = null;

                $('.elapsed-time-wrapper').hide();
                
                let formData = new FormData();

                try {
                    const res = await ajaxCall({
                        url: baseUrl + 'evaluate-session',
                        data: formData,
                        csrfHeader: '<?= csrf_header() ?>',
                        csrfHash: '<?= csrf_hash() ?>'
                    });

                    if (res.status == 'success') {

                        renderResult(res);
                    }
                    else {
                        new Notify({
                            title: 'Error',
                            text: res.message || 'Something went wrong',
                            status: 'error',
                            autoclose: true,
                            autotimeout: 3000
                        });
                    }
                }
                catch (err) {
                    new Notify({
                        title: 'Error',
                        text: err.responseJSON.message || 'Something went wrong',
                        status: 'error',
                        autoclose: true,
                        autotimeout: 3000
                    });
                }

                return;
            }

            renderQuestion(questions[currentQuestionIndex]);
        }

        function renderResult(result) {
            $("#questionsWrapper").empty();
            $(".question-footer, .question-solution").remove();

            $('#questionsWrapper').html(`
                <div class="result-wrapper">
                    <h5 class="card-title">Result</h5>
                    <p><strong>Correct Answers:</strong> ${result.correct_count}</p>
                    <p><strong>Incorrect Answers:</strong> ${result.incorrect_count}</p>
                    <p><strong>Elapsed Time:</strong> ${result.elapsed_time}</p>
                    <a href="${window.baseUrl}home" class="btn btn-primary">Restart Session</a>
                </div>
            `);
        }

        function renderQuestion(question) {
            $('#questionsWrapper').html(`
                <div class="question-item" data-id="questionid" style="display: block;">
                    <h5 class="card-title">${question.question_html}</h5>

                    <div class="answer-area">
                        ${(question.question_type == "text") ? `
                            <div class="answer-item">
                                <input type="text" class="form-control text-answer" autofocus>
                            </div>
                        ` : `
                            ${question.answers.map((answer, index) => `
                                <div class="answer-item mcq-option-wrapper">
                                    <label class="d-flex align-items-center" style="gap: 10px;">
                                        <input type="radio" name="answer" class="answer-radio" value='${base64EncodeUnicode(answer.answer)}'>
                                        ${answer.answer}
                                    </label>
                                </div>
                            `).join('')}
                        `}
                    </div>

                    <input type="hidden" id="questionType" value="${question.question_type}">
                    <input type="hidden" id="questionId" value="${question.id}">
                </div>
            `);

            // Force MathJax to re-render the newly loaded content
            MathJax.Hub.Queue([
                "Typeset",
                MathJax.Hub,
                $("#questionsWrapper").get(0),
            ]);
        }

        function base64EncodeUnicode(str) {
            return btoa(
                new TextEncoder().encode(str).reduce((data, byte) => data + String.fromCharCode(byte), '')
            );
        }

        function base64DecodeUnicode(str) {
            return new TextDecoder().decode(Uint8Array.from(atob(str), c => c.charCodeAt(0)));
        }

        // Code to calculate and display elapsed time
        let startTime = new Date();
        let elapsedTime = new Date().getTime() - startTime.getTime();
        let minutes = Math.floor(elapsedTime / 60000);
        let seconds = Math.floor((elapsedTime % 60000) / 1000);
        $("#elapsedTime").html(`${minutes}m ${seconds}s`);

        // Update elapsed time every second
        timeInterval = setInterval(() => {
            let elapsedTime = new Date().getTime() - startTime.getTime();
            let minutes = Math.floor(elapsedTime / 60000);
            let seconds = Math.floor((elapsedTime % 60000) / 1000);
            $("#elapsedTime").html(`${minutes}m ${seconds}s`);
        }, 1000);
    });

    if ('BroadcastChannel' in window) {
        const channel = new BroadcastChannel('tab-communication');
        const TAB_ID = Date.now().toString(); // Unique identifier for this tab
        const PAGE_ID = 'student-home-page';

        // Function to handle incoming messages
        channel.onmessage = (event) => {
            const { type, tabId, pageId } = event.data;

            if (pageId !== PAGE_ID) {
                console.log('Message ignored. Not intended for this page.');
                return;
            }

            if (type === 'NEW_TAB_OPENED' && tabId !== TAB_ID) {
                document.body.innerHTML = `
                    <div class="container pt-4">
                        <h3>This tab is now deactivated.</h3>
                        <p>You have opened this page in another tab.</p>
                    </div>
                `;
            }
        };

        // Notify other tabs that this tab is active
        function notifyNewTab() {
            channel.postMessage({
                type: 'NEW_TAB_OPENED',
                tabId: TAB_ID,
                pageId: PAGE_ID
            });
        }

        // Notify other tabs on page load
        notifyNewTab();

        // Clean up when the tab is closed
        window.addEventListener('beforeunload', () => {
            channel.close();
        });
    }
    else {
        console.warn('BroadcastChannel is not supported in this browser. Tab communication will not work.');
    }
</script>
<?= $this->endSection() ?>