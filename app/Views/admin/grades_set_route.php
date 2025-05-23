<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0"><i class="fa fa-route"></i>&nbsp; Set Route</h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5><?= $grade['grade_level'] ?></h5>
                </div>

                <div id="topicsContainer">
                    <?php foreach ($gradeRoute as $routeTopic) : ?>
                        <div class="d-flex justify-content-between align-items-center mb-2" style="gap: 10px;">
                            <div class="flex-grow-1 added-topic" data-id="<?= $routeTopic['topic_id'] ?>"><?= $routeTopic['topic']['name'] ?></div>
                            <button class="btn btn-sm btn-danger rounded-circle remove-topic-btn" data-toggle="tooltip" title="Remove Topic">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center" style="gap: 10px;">
                    <div class="flex-grow-1">
                        <select class="form-control" id="topicSelect">
                            <option value="">Select Topic</option>
                            <?php foreach ($topics as $topic) : ?>
                                <option value="<?= $topic['id'] ?>"><?= $topic['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-dark rounded-circle" id="addTopicBtn" data-toggle="tooltip" title="Add Topic">
                        <i class="fa fa-plus"></i>
                    </button>
                </div>

                <input type="hidden" id="gradeId" value="<?= $grade['id'] ?>">

                <div class="d-flex justify-content-end mt-4">
                    <button class="btn btn-sm btn-primary" id="saveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script>
    $(document).ready(function() {
        $('#saveBtn').click(async function() {

            let gradeId = $('#gradeId').val();
            let topics = [];

            $('.added-topic').each(function() {
                topics.push($(this).data('id'));
            });

            if (topics.length === 0) {
                new Notify({
                    title: 'Error',
                    text: 'Please add at least one topic',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
            }

            try {

                let formData = new FormData();

                formData.append('grade_id', gradeId);
                formData.append('topics', topics.join(','));

                let res = await ajaxCall({
                    url: baseUrl + 'admin/grades/updateRoute',
                    data: formData,
                    csrfHeader: '<?= csrf_header() ?>',
                    csrfHash: '<?= csrf_hash() ?>'
                });

                if (res.status == 'success') {
                    window.location.reload();
                    return;
                }
                else {
                    new Notify({
                        title: 'Error',
                        text: res.message,
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
        });

        $('#addTopicBtn').click(function() {
            
            let topicId = $('#topicSelect').val();

            if (!topicId) {
                new Notify({
                    title: 'Error',
                    text: 'Please select a topic',
                    status: 'error',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            if ($(`.added-topic[data-id="${topicId}"]`).length > 0) {
                new Notify({
                    title: 'Warning',
                    text: 'Topic already added',
                    status: 'warning',
                    autoclose: true,
                    autotimeout: 3000
                });
                return;
            }

            // Get the option text
            let topicName = $('#topicSelect option:selected').text();

            $("#topicsContainer").append(
                `<div class="d-flex justify-content-between align-items-center mb-2" style="gap: 10px;">
                    <div class="flex-grow-1 added-topic" data-id="${topicId}">${topicName}</div>
                    <button class="btn btn-sm btn-danger rounded-circle remove-topic-btn" data-toggle="tooltip" title="Remove Topic">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>`);
            
            $('#topicSelect').val('');
        });

        $(document).on('click', '.remove-topic-btn', function() {
            $(this).parent().remove();
        });
    });
</script>
<?= $this->endSection() ?>