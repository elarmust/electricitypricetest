<!DOCTYPE html>
<html lang="et">
    <head><meta charset="utf-8"><title>Spotihindade tulemus</title></head>
    <body style="font-family: sans-serif; font-size: 14px; color: #1f2933;">
        <h2>Spotihindade tulemuse esitus</h2>
        <table style="border-collapse: collapse; width: 100%; max-width: 600px;">
            @foreach([
                'Kandidaadi nimi' => $data['name'],
                'E-post' => $data['email'],
                'Telefon' => $data['phone'],
                'GitHub repositoorium' => $data['repo'],
                'Viimane commit (SHA)' => $data['commit'],
                'Valitud kuupäev' => $data['date'],
                'Hinnapiirkond' => $data['region'],
                'Keskmine hind (EUR/MWh)' => $data['average'],
                'Miinimum (EUR/MWh)' => $data['min'],
                'Maksimum (EUR/MWh)' => $data['max'],
                'Odavaim aken (algus, Tallinn)' => $data['cheapestStart'],
                'Odavaim aken (keskmine, EUR/MWh)' => $data['cheapestAvg'],
                'Saatmise aeg (Europe/Tallinn)' => $data['sentAt'],
                'PHP versioon' => $data['phpVersion'],
            ] as $label => $value)
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #ddd; font-weight: bold; width: 55%;">{{ $label }}</td>
                    <td style="padding: 6px 10px; border: 1px solid #ddd;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </body>
</html>
