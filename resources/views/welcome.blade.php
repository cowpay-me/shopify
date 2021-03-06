@extends('layouts.default')
@section('content')
<div class="container configurations-index" >
    <div class="card">
        <div class="card-header">
            <h5 class="card-title">FawryPay Payment Settings</h5>
            <p class="card-subtitle mb-2 text-muted">Your integration keys are necessary to integrate to FawryPay Payments API. They should be protected the same way you protect your passwords.</p>
        </div>

        <div class="card-body">
            {{ Form::open(array('route' => 'set-config', 'id' => 'square-config-form'))}}
            @if (count($errors) > 0)
            <div class="alert alert-danger">
                There were some problems.<br />
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
             <div class="seo-setting col-md-8">   
                <div class="row col-md-12">
                    <div class="col-lg-12 mb-3">
                        <h5>Shopify-Partner Application Info</h5>
                    </div> 
                </div>

                
                <div class="col-md-8">
                    {!! htmlspecialchars_decode(Form::label('Application Name', '', array('class' => 'col-form-label'))) !!}
                    {!! Form::text('app_name', $value =  $settings ? $settings['app_name'] : '', 
                    array(
                    'class'=>'form-control', 
                    'placeholder'=>'Enter Application Name'
                    )
                    )
                    !!}
                </div>
                <div class="col-md-8">
                    {!! htmlspecialchars_decode(Form::label('Application Key', '', array('class' => 'col-form-label'))) !!}
                    {!! Form::text('app_key', $value =  $settings ? $settings['app_key'] : '', 
                    array(
                    'class'=>'form-control', 
                    'placeholder'=>'Enter Application Key'
                    )
                    )
                    !!}
                </div>
                 <div class="col-md-8">
                    {!! htmlspecialchars_decode(Form::label('Application Secret', '', array('class' => 'col-form-label'))) !!}
                    {!! Form::text('app_secret', $value =  $settings ? $settings['app_secret'] : '', 
                    array(
                    'class'=>'form-control', 
                    'placeholder'=>'Enter Application Secret'
                    )
                    )
                    !!}
                </div>
                
            </div>
        
            
            <div class="seo-setting col-md-8">             
                <div class="row col-md-12">
                    <div class="col-lg-12 mt-3 mb-3">
                        <h5>Production Integration keys</h5>
                    </div> 
                </div>
                <div class="row col-md-12">
                    <h6 class="col-md-4 col-sm-12 col-sx-12">Enable Production Account</h6> 
                    <span class="col-md-8 col-sm-12 col-sx-12">
                        <input id="enable_live" type="checkbox" name="enable_live" value="1" {{$settings && $settings['enable_live'] == 1 ?'checked' :''}}>

                    </span>
                </div>
                <div class="col-md-8">
                    {!! htmlspecialchars_decode(Form::label('Account Number', '', array('class' => 'col-form-label'))) !!}
                    {!! Form::text('live_account_number', $value =  $settings ? $settings['live_account_number'] : '', 
                    array(
                    'class'=>'form-control', 
                    'placeholder'=>'Enter Account Number'
                    )
                    )
                    !!}
                </div>
                <div class="col-md-8">
                    {!! htmlspecialchars_decode(Form::label('Secure hash key', '', array('class' => 'col-form-label'))) !!}
                    {!! Form::text('live_hash_key', $value =  $settings ? $settings['live_hash_key'] : '', 
                    array(
                    'class'=>'form-control', 
                    'placeholder'=>'Enter Secure hash key'
                    )
                    )
                    !!}
                </div>
            </div>
            <hr class="mt-5 mb-5"/>
            <div class="seo-setting col-md-8">   
                <div class="row col-md-12">
                    <div class="col-lg-12 mb-3">
                        <h5>Sandbox Integration keys</h5>
                    </div> 
                </div>
                <div class="row col-md-12">
                    <h6 class="col-lg-4 col-md-6 col-sm-12 col-sx-12">Enable Sandbox Account</h6> 
                    <span class="col-lg-8 col-md-6 col-sm-12 col-sx-12">
                        <input type="checkbox" id="sandbox" name="sandbox" value="1" {{$settings && $settings['sandbox'] == 1 ?'checked' :''}} >
                    </span>
                </div>
                <div class="col-md-8">
                    {!! htmlspecialchars_decode(Form::label('Account Number', '', array('class' => 'col-form-label'))) !!}
                    {!! Form::text('sandbox_account_number', $value =  $settings ? $settings['sandbox_account_number'] : '', 
                    array(
                    'class'=>'form-control', 
                    'placeholder'=>'Enter Account Number'
                    )
                    )
                    !!}
                </div>
                 <div class="col-md-8">
                    {!! htmlspecialchars_decode(Form::label('Secure hash key', '', array('class' => 'col-form-label'))) !!}
                    {!! Form::text('sandbox_hash_key', $value =  $settings ? $settings['sandbox_hash_key'] : '', 
                    array(
                    'class'=>'form-control', 
                    'placeholder'=>'Enter Secure hash key'
                    )
                    )
                    !!}
                </div>
                <div class="row">
                    <span class="col-lg-10 col-md-10 col-sm-12 col-sx-12 text-right mt-3 mb-3"> 
                        {{ Form::submit('Submit' , array('class' => 'btn btn-primary')) }}
                    </span>
                </div>	
            </div>
            {{ Form::close() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
@parent

<script type="text/javascript">
    var AppBridge = window['app-bridge'];
    var actions = AppBridge.actions;
    var TitleBar = actions.TitleBar;
    var Button = actions.Button;
    var Redirect = actions.Redirect;
    var titleBarOptions = {
        title: 'Welcome',
    };
    var myTitleBar = TitleBar.create(app, titleBarOptions);
</script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
<script src="{{ asset('/js/developer.js') }}" rel="stylesheet"></script>
@endsection
