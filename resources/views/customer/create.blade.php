@extends('layouts.admin')
@section('css')
<style>
      .hidden {
            display: none;
      }

      .loading-spinner {
            width: 30px;
            height: 30px;
            border: 4px solid #ccc;
            border-top-color: #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: inline-block;
      }

      @keyframes spin {
            0% {
                  transform: rotate(0deg);
            }

            100% {
                  transform: rotate(360deg);
            }
      }
</style>
@endsection
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

      @if($errors->any())
      <script>
            toastr.error("{{ $errors->all() }}");
      </script>
      @endif
      @if(session('error'))
      <script>
            toastr.error("{{ session('error') }}");
      </script>
      @endif
      <form action="{{url('customer/store')}}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                  <div class="card-header bg-transparent">
                        <h3 class="mb-0 text-gray-800">{{ isset($customer)?'Edit':'Create' }} Customer</h3>
                  </div>
                  <div class="card-body">
                        <!-- Content Row -->
                        <div class="row">
                              <input type="hidden" name="id" value="{{ isset($customer)?$customer->id:'' }}">
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Name:<span class="text-danger">*</span></label>
                                          <input type="text" id="name" name="name" placeholder="Enter customer name" value="{{ isset($customer)?$customer->name:old('name') }}" class="form-control" required />
                                          @error('name')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Email:<span class="text-danger">**</span></label>
                                          <input type="email" id="email" name="email" placeholder="Enter customer email" value="{{ isset($customer)?$customer->email:old('email') }}" class="form-control" required />
                                          @error('email')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Phone No:<span class="text-danger">**</span></label>
                                          <input type="text" id="phone_no" name="phone_no" placeholder="Enter customer phone no" value="{{ isset($customer)?$customer->phone_no:old('phone_no') }}" class="form-control" required />
                                          @error('phone_no')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Domain Name:<span class="text-danger">**</span></label>
                                          <input type="text" id="subdomain" name="subdomain" placeholder="Enter domain name" value="{{ isset($customer)?$customer->subdomain:old('subdomain') }}" {{ isset($customer)?'disabled':'' }} class="form-control" required />
                                          @error('subdomain')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Plan:<span class="text-danger">*</span></label>
                                          <select name="plan_id" id="plan_id" class="form-control" required>
                                                <option value="" disabled>--Select Plan--</option>
                                                @foreach($plans as $item)
                                                <option value="{{$item->id}}" {{(isset($customer) && $customer->plan_id==$item->id)?'selected':''}}>{{$item->name??''}}</option>
                                                @endforeach
                                          </select>
                                          @error('plan_id')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                        </div>
                  </div>
                  <div class="card-footer">
                        <div class="col-md-12">
                              <button class="btn btn-primary" id="submit" accesskey="s">{{ isset($customer)?'Update':'Save' }}</button>
                              <span id="loading" class="hidden">
                                    <div class="loading-spinner"></div> Processing...
                              </span>
                        </div>
                  </div>
            </div>
      </form>
</div>
<!-- /.container-fluid -->

@endsection
@section('js')
<script>
      function isNumberKey(evt) {
            var charCode = evt.which ? evt.which : evt.keyCode;
            if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
                  return false;

            return true;
      }
      document.getElementById('submit').addEventListener('submit', function(event) {
            $('#submit').hide();
            $('#loading').show(); // Show Loading
      });

      // If validation errors exist, show Save button again after reload
      @if($errors->any())
      $('#submit').hide();
      $('#loading').hide();
      @endif
</script>
@endsection