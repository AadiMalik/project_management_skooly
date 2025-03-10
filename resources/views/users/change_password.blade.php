@extends('layouts.admin')
@section('content')
<div class="container-fluid">
      @if(session('error'))
      <script>
            toastr.error("{{ session('error') }}");
      </script>
      @endif
      @if(session('success'))
      <script>
            toastr.success("{{ session('success') }}");
      </script>
      @endif
      <form action="{{url('users/update-password')}}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                  <div class="card-header bg-transparent">
                        <h3 class="mb-0 text-gray-800">Change Password</h3>
                  </div>
                  <div class="card-body">
                        <!-- Content Row -->
                        <div class="row">
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">New Password:<span class="text-danger">*</span></label>
                                          <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="false" class="form-control" required />
                                          @error('password')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Confirm Password:<span class="text-danger">*</span></label>
                                          <input type="password" id="password-confirm" name="password_confirmation" placeholder="Enter confirm password" class="form-control" required />
                                    </div>
                              </div>
                        </div>
                  </div>
                  <div class="card-footer">
                        <div class="col-md-12">
                              <button class="btn btn-primary" id="submit" accesskey="s">Save Change</button>
                        </div>
                  </div>
            </div>
      </form>
</div>
<!-- /.container-fluid -->
@endsection