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
                'GitHub repo' => $data['repo'],
                'Viimane commit (SHA)' => $data['commit'],
                'Valitud kuupäev' => $data['date'],
                'Akna pikkus (h)' => $data['window'],
                'Hinnapiirkond' => $data['region'],
                'Keskmine hind (€/MWh, ilma KM)' => $data['average'],
                'Keskmine hind (€/MWh, KM-ga)' => $data['averageVat'],
                'Miinimum (€/MWh, ilma KM)' => $data['min'],
                'Miinimum (€/MWh, KM-ga)' => $data['minVat'],
                'Maksimum (€/MWh, ilma KM)' => $data['max'],
                'Maksimum (€/MWh, KM-ga)' => $data['maxVat'],
                'Odavaim aken (algus, Tallinn)' => $data['cheapestStart'],
                'Odavaim aken (keskmine, €/MWh, ilma KM)' => $data['cheapestAvg'],
                'Odavaim aken (keskmine, €/MWh, KM-ga)' => $data['cheapestAvgVat'],
                'Saatmise aeg (Europe/Tallinn)' => $data['sentAt'],
                'PHP versioon' => $data['phpVersion'],
            ] as $label => $value)
                <tr>
                    <td style="padding: 6px 10px; border: 1px solid #ddd; font-weight: bold; width: 55%;">{{ $label }}</td>
                    <td style="padding: 6px 10px; border: 1px solid #ddd;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
        <p>Kõik hinnad sisaldavad edastustasu {{ $data['transmissionFee'] }} €/mWh.</p>
    </body>
</html>
