<x-mail::message>
# Nuevo comentario en tu publicación

**{{ $author }}** comentó en tu publicación **"{{ $post->title }}"**:

<x-mail::panel>
{{ $text }}
</x-mail::panel>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
