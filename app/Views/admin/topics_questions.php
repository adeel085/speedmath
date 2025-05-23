<?= $this->extend('admin/Layouts/default') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Questions for <?= $topic['name'] ?></h4>

            <div class="page-title-right">

            </div>

        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">

                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th style="border-top: none;">Level</th>
                                <th style="border-top: none;">Question Type</th>
                                <th style="border-top: none;">Question</th>
                                <th style="border-top: none;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $question) : ?>
                                <tr data-id="<?= $question['id'] ?>">
                                    <td><?= $question['level'] ?></td>
                                    <td><?= $question['question_type'] ?></td>
                                    <td><?= strip_tags($question['question_html']) ?></td>
                                    <td>
                                        <div class="d-flex justify-content-end table-action-btn" style="gap: 10px;">
                                            <a href="<?= base_url('/admin/questions/edit/' . $question['id']) ?>" class="table-action-btn" data-toggle="tooltip" title="Edit Question">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="javascript:void(0)" class="table-action-btn remove-btn" data-toggle="tooltip" title="Remove Question">
                                                <i class="fa fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    <?= $pager->links() ?>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" id="topicId" value="<?= $topic['id'] ?>">

<?= $this->endSection() ?>

<?= $this->section('foot') ?>
<script>
    $(() => {

        $(".remove-btn").click(async function() {

            if (!confirm("Are you sure you want to remove this question from the topic?")) {
                return;
            }

            let questionId = $(this).closest("tr").data("id");

            try {
                let formData = new FormData();
                formData.append('topicId', $("#topicId").val());
                formData.append('questionId', questionId);

                let res = await ajaxCall({
                    url: baseUrl + 'admin/topics/remove-question',
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
        });
    });
</script>
<?= $this->endSection() ?>