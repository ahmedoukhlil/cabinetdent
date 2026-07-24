<div style="width: 100%; margin-bottom: 10px;">
    @php
        $headerImagePath = public_path('entetesavwa.png');
        $imageExists = file_exists($headerImagePath);
        $imageBase64 = null;
        if ($imageExists) {
            $imageData = file_get_contents($headerImagePath);
            $imageBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        }
        // Fallback vers l'ancienne image si la nouvelle n'existe pas
        if (!$imageBase64) {
            $oldImagePath = public_path('EnteteMedipole.jpg');
            if (file_exists($oldImagePath)) {
                $oldImageData = file_get_contents($oldImagePath);
                $imageBase64 = 'data:image/jpeg;base64,' . base64_encode($oldImageData);
            }
        }
    @endphp
    @if($imageBase64)
    <img src="{{ $imageBase64 }}" alt="En-tête du cabinet" style="width: 100%; max-width: 100%; height: auto; display: block;" />
    @endif
</div> 