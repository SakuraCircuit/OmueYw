@php
    try {
        $appHtml = \Illuminate\Support\Facades\File::get(public_path('index.html'));

        // 过滤生产环境不需要的调试入口脚本（Vue DevTools 等）
        $appHtml = preg_replace('/<script[^>]*src="[^"]*devtools[^"]*"[^>]*><\/script>/i', '', $appHtml);

        echo $appHtml;
    } catch (Exception $e) {
        echo '未获取到页面内容,请检查public目录下是否存在编译后的前端项目!!!';
    }
@endphp

{{--插入自定义脚本--}}
@if(config("hklist.general.custom_script") !== "")
    <script>
        {!! config("hklist.general.custom_script") !!}
    </script>
@endif
