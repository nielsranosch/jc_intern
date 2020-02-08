<a href="{{ $href }}"
   title="{{ $title }}"
   class="btn btn-2d btn-add pull-right {{ implode(' ', $classes) }}" <?php
        foreach ($attributes as $key => $attribute) {
            echo $key . '="' . $attribute . '" ';
        }
        ?>>
    <i class="fa fa-{{ $icon }}"></i>
    <span class="title">&nbsp;{{ $title }}</span>
</a>