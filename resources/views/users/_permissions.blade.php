@php
    $selectedPermissions = old('permissions', isset($user) ? ($user->permissions ?? []) : []);
@endphp

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-white py-3">
        <h3 class="h6 mb-1">Module Permissions</h3>
        <p class="mb-0 small text-muted">Choose what this user can view, add, update, and delete in each module.</p>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-flush mb-0 permissions-table">
                <thead class="thead-light">
                    <tr>
                        <th class="ps-3">Module</th>
                        @foreach ($actions as $actionKey => $actionLabel)
                            <th class="text-center">{{ $actionLabel }}</th>
                        @endforeach
                        <th class="text-center pe-3">All</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($modules as $moduleKey => $moduleLabel)
                        <tr>
                            <td class="ps-3 fw-semibold">{{ $moduleLabel }}</td>
                            @foreach ($actions as $actionKey => $actionLabel)
                                <td class="text-center">
                                    <input type="checkbox"
                                        class="form-check-input permission-checkbox"
                                        name="permissions[{{ $moduleKey }}][{{ $actionKey }}]"
                                        value="1"
                                        data-module="{{ $moduleKey }}"
                                        @checked(! empty($selectedPermissions[$moduleKey][$actionKey]))>
                                </td>
                            @endforeach
                            <td class="text-center pe-3">
                                <input type="checkbox" class="form-check-input permission-row-all" data-module="{{ $moduleKey }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        (function () {
            function syncRowAll(module) {
                var boxes = document.querySelectorAll('.permission-checkbox[data-module="' + module + '"]');
                var rowAll = document.querySelector('.permission-row-all[data-module="' + module + '"]');
                if (!rowAll || !boxes.length) return;
                rowAll.checked = Array.from(boxes).every(function (box) { return box.checked; });
            }

            document.querySelectorAll('.permission-row-all').forEach(function (rowAll) {
                rowAll.addEventListener('change', function () {
                    var module = rowAll.dataset.module;
                    document.querySelectorAll('.permission-checkbox[data-module="' + module + '"]').forEach(function (box) {
                        box.checked = rowAll.checked;
                    });
                });
            });

            document.querySelectorAll('.permission-checkbox').forEach(function (box) {
                box.addEventListener('change', function () {
                    syncRowAll(box.dataset.module);
                });
                syncRowAll(box.dataset.module);
            });
        })();
    </script>
@endpush
