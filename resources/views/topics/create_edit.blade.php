@extends('layouts.default')

@section('title')
    {{ lang('Create New Topic') }}_@parent
@stop

@section('content')
    <div class="topic_create">
        <div class="col-md-8 main-col">
            <div class="reply-box form box-block">
                <div class="alert alert-warning">
                    {{ lang('be_nice') }}
                </div>

                @include('layouts.partials.errors')

                @if (isset($topic))

                @else
                {{ Form::open(['url' => route('topics.store'), 'id' => 'topic-create-form', 'accept-charset' => 'UTF-8']) }}
                @endif
                    <div class="form-group">
                        <select class="selectpicker form-control" name="category_id" >
                            <option value="" disabled{{ count($category) !== 0 ? ' selected' : ''}}>
                                {{ lang('Pick a category') }}
                            </option>

                            @foreach($categories as $value)
                                <option value="{{ $value->id }}"{{ (count($category) != 0 && $category->id == $value->id) ? ' selected' : '' }}>
                                    {{ $value->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    <div class="form-group">
                        {{ Form::text('title', null, ['id' => 'topic-title', 'class' => 'form-control', 'placeholder' => lang('Please write down a topic')]) }}
                    </div>

                    @include('topics.partials.composing_help_block')

                    <div class="form-group">
                        {{ Form::textarea('body', (isset($topic) ? $topic->body_original : ''), ['id' => 'reply_content', 'class' => 'form-control', 'rows' => 20, 'cols' => 50, 'placeholder' => lang('Please using markdown.'), 'style' => 'overflow: hidden']) }}
                    </div>

                    <div class="form-group status-post-submit">
                        {{ Form::submit(lang('Publish'), ['id' => 'topic-create-submit', 'class' => 'btn btn-primary']) }}
                    </div>

                    <div class="box preview markdown-body" id="preview-box" style="display: none;"></div>
                {{ Form::close() }}
            </div>
        </div>

        <div class="col-md-4 side-bar">
            @if ($category)
                <div class="panel panel default corner-radius help-box">
                    <div class="panel-heading text-center">
                        <h3 class="panel-title">
                            {{ lang('Current Category') }} : {{ $category->name }}
                        </h3>
                    </div>
                    <div class="panel-body">
                        {{ $category->description }}
                    </div>
                </div>
            @endif

            <div class="panel panel-default corner-radius help-box">
                <div class="panel-heading text-center">
                    <h3 class="panel-title">{{ lang('This kind of topic is not allowed.') }}</h3>
                </div>
                <div class="panel-body">
                    <ul class="list">
                        <li>请传播美好的事物，这里拒绝低俗、诋毁、谩骂等相关信息</li>
                        <li>请尽量分享技术相关的话题，谢绝发布社会, 政治等相关新闻</li>
                        <li>这里绝对不讨论任何有关盗版软件、音乐、电影如何获得的问题</li>
                    </ul>
                </div>
            </div>

            <div class="panel panel-default corner-radius help-box">
                <div class="panel-heading text-center">
                    <h3 class="panel-title">{{ lang('We can benefit from it.') }}</h3>
                </div>
                <div class="panel-body">
                    <ul class="list">
                        <li>分享生活见闻, 分享知识</li>
                        <li>接触新技术, 讨论技术解决方案</li>
                        <li>为自己的创业项目找合伙人, 遇见志同道合的人</li>
                        <li>自发线下聚会, 加强社交</li>
                        <li>发现更好工作机会</li>
                        <li>甚至是开始另一个神奇的开源项目</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop