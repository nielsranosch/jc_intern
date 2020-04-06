<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
        // Easily accessible form-inputs with right styling etc.
        \Form::component('textInput2d', 'components.form.text', ['name', 'value' => null, 'attributes' => [], 'helpBubble' => false]);
        \Form::component('numberInput2d', 'components.form.number', ['name', 'value' => null, 'attributes' => [], 'helpBubble' => false]);
        \Form::component('textareaInput2d', 'components.form.textarea', ['name', 'value' => null, 'attributes' => [], 'helpBubble' => false]);
        \Form::component('dateInput2d', 'components.form.date', ['name', 'value' => null, 'attributes' => [], 'helpBubble' => trans('form.help_date')]);
        \Form::component('datetimeInput2d', 'components.form.datetime', ['name', 'value' => null, 'attributes' => [], 'helpBubble' => trans('form.help_dateTime')]);
        \Form::component('checkboxInput2d', 'components.form.checkbox', ['name', 'value' => null, 'attributes' => [], 'helpBubble' => false]);
        \Form::component('radioInput2d', 'components.form.radio', ['name', 'label', 'value' => 1, 'checked' => false, 'attributes' => [], 'helpBubble' => false]);
        \Form::component('selectInput2d', 'components.form.select', ['name', 'list' => [], 'selected' => null, 'attributes' => [], 'helpBubble' => false]);
        \Form::component('passwordInput2d', 'components.form.password', ['name', 'attributes' => [], 'helpBubble' => false]);

        // Form helper bubble for some field.
        \Form::component('helpBubble', 'components.form.helpBubble', ['content' => '']);
        
        // Buttons
        \Html::component('button', 'components.button.btn', ['title', 'href', 'icon', 'classes' => [], 'attributes' => []]);
        \Html::component('addButton', 'components.button.btn', ['title', 'href', 'classes' => [], 'attributes' => [], 'icon' => 'plus']);
        \Html::component('button2d', 'components.button.button', ['title', 'href', 'icon', 'classes' => [], 'attributes' => []]);
        \Form::component('submitInput2d', 'components.button.submit', ['title' => trans('form.save'), 'attributes' => ['name' => 'submit', 'value' => trans('form.save')]]);
        \Form::component('submitDelete2d', 'components.button.delete', ['title' => trans('form.delete'), 'attributes' => ['name' => 'submit', 'value' => trans('form.delete')]]);

        // Versioned form of Html::style and Html::script for cache busting, which use timestamp of lastmodified as GET-Parameter
        \Html::component('styleV', 'components.html.style-versioned', ['filename']);
        \Html::component('scriptV', 'components.html.script-versioned', ['filename']);

        // Add an urlescape directive to Blade templates.
        \Blade::directive('urlescape', function ($expression) {
            return '<?php echo urlencode(' . $expression . '); ?>';
        });


        //TODO: move this to a dedicated class
        \Validator::extend('custom_complexity', function($attribute, $value, $parameters, $validator) {
            /**
             * Function to check if a password satisfies a required 'complexity level'. Two things affect the complexity level: length and types of characters used
             *
             * $value is being checked against 4 categories: lowercase, uppercase, digits and none of the above.
             * Each category used means one complexity level up.
             *
             * After 8 charachters, adding 2 characters will gain one complexity level.
             */

            $required_complexity = (int) $parameters[0];
            $complexity = 0;
            $categories = [
                '\d',            // digits
                '\p{Ll}\p{Lo}',  // lowercase and other
                '\p{Lu}\p{Lt}',  // uppercase and titlecase
            ];

            if (mb_strlen($value) > 8) {
                // We encourage long passwords
                $complexity += (int) ((mb_strlen($value) - 8) / 2);
            }

            if ($complexity >= $required_complexity) {
                return true;
            }

            $before = $after = $value;
            foreach($categories as $category) {
                $pattern = '[' . $category . ']';
                $after = mb_ereg_replace($pattern, '', $before);

                if (mb_strlen($before) !== mb_strlen($after)) {
                    $complexity += 1;
                    if ($complexity >= $required_complexity) {
                        return true;
                    }
                    $before = $after;
                };
            }
            $value = $after;

            if (mb_strlen($value) > 0) {
                // At least one character which didnt fall in any other category. (OR: the loop ended before all categories were checked, which is also fine since the success-condition was already met)
                $complexity += 1;
            }

            return $complexity >= $required_complexity;
        });

        \Validator::extend('alpha_dash_space', function($attribute, $value, $parameters, $validator) {
            return mb_ereg_match('^[\p{L}\s\-]+$', $value);
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
