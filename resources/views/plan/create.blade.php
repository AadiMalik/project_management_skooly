@extends('layouts.admin')

@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

      <!-- Page Heading -->
      <!-- <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Create Subscription Plan</h1>
      </div> -->
      @if(session('error'))
      <script>
            toastr.error("{{ session('error') }}");
      </script>
      @endif
      <!-- @if ($errors->any())
      <div class="alert alert-danger">
            <ul>
                  @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                  @endforeach
            </ul>
      </div>
      @endif -->
      <form action="{{url('plan/store')}}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card">
                  <div class="card-header bg-transparent">
                        <h3 class="mb-0 text-gray-800">{{ isset($plan)?'Edit':'Create' }} Subscription Plan</h3>
                  </div>
                  <div class="card-body">
                        <!-- Content Row -->
                        <div class="row">
                              <input type="hidden" name="id" value="{{ isset($plan)?$plan->id:'' }}">
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Name:<span class="text-danger">**</span></label>
                                          <input type="text" id="name" name="name" placeholder="Enter plan name" value="{{ isset($plan)?$plan->name:old('name') }}" class="form-control" required />
                                          @error('name')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Price:<span class="text-danger">*</span></label>
                                          <input type="text" id="price" name="price" onkeypress="return isNumberKey(event)" placeholder="Enter plan price" value="{{ isset($plan)?$plan->price:old('price') }}" class="form-control" required />
                                          @error('price')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                              <div class="col-md-6">
                                    <div class="form-group">
                                          <label class="form-label">Days:<span class="text-danger">*</span></label>
                                          <input type="number" id="days" name="days" placeholder="Enter plan days" value="{{ isset($plan)?$plan->days:old('days') }}" class="form-control" required />
                                          @error('days')
                                          <span class="text-danger">{{ $message }}</span>
                                          @enderror
                                    </div>
                              </div>
                        </div>
                  </div>
                  <div class="card-footer">
                        <div class="col-md-12">
                              <button class="btn btn-primary" id="submit" accesskey="s">{{ isset($plan)?'Update':'Save' }}</button>
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
</script>
@endsection