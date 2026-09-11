@if ($errors->any())
    <div class="col-12">
        <div class="alert alert-warning ams-alert mb-3" role="alert" data-auto-dismiss="true">
            <div class="flex-grow-1">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif

@if (session('success'))
    <div class="col-12">
        <div class="alert alert-success ams-alert mb-3" role="alert" data-auto-dismiss="true">
            <span class="flex-grow-1">{{ session('success') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif

@if (session('error'))
    <div class="col-12">
        <div class="alert alert-danger ams-alert mb-3" role="alert" data-auto-dismiss="true">
            <span class="flex-grow-1">{{ session('error') }}</span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
@endif
