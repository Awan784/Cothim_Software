@extends('template.layout')
@section('title', 'Office Users List')
@section('content')
    <!-- Delete Modal Start  -->
    <div class="modal fade" id="modal-notification" tabindex="-1" role="dialog" aria-labelledby="modal-notification"
        aria-hidden="true">
        <div class="modal-dialog modal-info modal-dialog-centered" role="document">
            <form method="post" action="" id="delete-form" style="width:100% !important">
                @csrf
                <input type="hidden" name="_method" value="delete">
                <div class="modal-content bg-gradient-danger">
                    <button type="button" class="btn-close theme-settings-close fs-6 ms-auto" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                    <div class="modal-header">

                    </div>
                    <div class="text-white modal-body">

                        <div class="py-3 text-center">
                            <span class="modal-icon">
                                <svg class="text-gray-200 icon icon-xl" fill="currentColor" viewBox="0 0 20 20"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </span>
                            <h2 class="my-3 h4 modal-title">Important message!</h2>
                            <p>Do you know to delete this User ?</p>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-sm btn-white">Yes</button></div>
                </div>
            </form>
        </div>
    </div>
    <!-- Delete Modal End  -->
    <div class="pb-4">
        <div class="py-4">

            <div class="flex-wrap d-flex justify-content-between w-100">
                <div class="mb-3 mb-lg-0">
                    <h1 class="h4">Office Users List</h1>
                    <p class="mb-0">List of all the office users in the system.</p>
                </div>
                <div>
                    @can('module-access', ['users', 'create'])
                    <a href="{{ route('users.create') }}"
                        class="mt-3 btn btn-sm btn-gray-800 d-inline-flex align-items-center">
                        <svg class="icon icon-xs me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Add New User
                    </a>
                    @endcan
                </div>

            </div>
        </div>

        <div class="card py">
            <div class="py-4 table-responsive">
                <table class="table table-flush" id="datatable">
                    <thead class="thead-light">
                        <tr>
                            <th class="border-bottom fw-bolder" scope="col">Office User Name</th>
                            <th class="border-bottom fw-bolder" scope="col">Email</th>
                            <th class="border-bottom fw-bolder" scope="col">Password</th>
                            <th class="border-bottom fw-bolder" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr @class(['table-info' => $user->id === auth()->id()])>
                                <td class="text-gray-900">
                                    {{ $user->name }}
                                    @if ($user->id === auth()->id())
                                        <span class="badge bg-primary ms-1">You</span>
                                    @endif
                                    @if ($user->isAdmin())
                                        <span class="badge bg-secondary ms-1">Admin</span>
                                    @endif
                                </td>
                                <td class="text-gray-900">{{ $user->email }}</td>
                                <td class="text-gray-900">{{ $user->show_password }}</td>
                                <td>
                                    @if (auth()->user()?->canModule('users', 'update') || $user->id === auth()->id())
                                    <a href="{{ route('users.edit', $user->id) }}"
                                        class="btn btn-outline-primary action-btn d-inline-flex align-items-center">
                                        <svg class="icon icon-xxs me-1" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M17.414 2.586a2 2 0 00-2.828 0L7 10.172V13h2.828l7.586-7.586a2 2 0 000-2.828z">
                                            </path>
                                            <path fill-rule="evenodd"
                                                d="M2 6a2 2 0 012-2h4a1 1 0 010 2H4v10h10v-4a1 1 0 112 0v4a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"
                                                clip-rule="evenodd"></path>
                                        </svg> Edit
                                    </a>
                                    @endif
                                    @can('module-access', ['users', 'delete'])
                                    @if ($user->id !== auth()->id())
                                    <button type="button" onclick="setDeleteUser({{ $user->id }})"
                                        class="btn btn-outline-danger action-btn d-inline-flex align-items-center"
                                        data-bs-toggle="modal" data-bs-target="#modal-notification">
                                        <svg class="icon icon-xxs me-1" fill="currentColor" viewBox="0 0 20 20"
                                            xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd"
                                                d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                                clip-rule="evenodd"></path>
                                        </svg> Delete
                                    </button>
                                    @endif
                                    @endcan
                                </td>
                            </tr>
                        @endforeach



                    </tbody>
                </table>
            </div>
        </div>
    </div>



@endsection
@section('scripts')
    <script>
        const usersBaseUrl = "{{ url('/users') }}";

        function setDeleteUser(id) {
            document.getElementById('delete-form').action = `${usersBaseUrl}/${id}`;
        }
    </script>

@endsection
