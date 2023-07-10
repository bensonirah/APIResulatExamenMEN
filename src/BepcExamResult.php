<?php

namespace Miavaka\MenExamResult;

final class BepcExamResult implements ExamResultInterface
{

    const TYPE_NAME = 'nom';
    const TYPE_MATRICULE = 'mle';
    const EXAM_BEPC = 'bepc';

    public function search(string $query): string
    {
        $typeRec = self::TYPE_NAME;
        if ($this->validerMatricule($query)) {
            $typeRec = self::TYPE_MATRICULE;
        }
        $result = json_decode(json_encode($this->get($query, $typeRec, self::EXAM_BEPC)));
        if (!$result->success) {
            return $result->error_msg;
        }

        $result_str = '';
        foreach ($result->data as $data) {
            $result_str .= sprintf(
                "\n\nN° Matricule: %s\nNOM et Prénom(s): %s\nCISCO: %s\nÉcole d'origine: %s\nObservation: %s",
                $data->matricule,
                $data->nom,
                $data->cisco,
                $data->ecole_origine,
                $data->observation
            );
        }

        return trim($result_str);
    }


    /**
     * Recherche de résultat d'examen
     *
     * @param string $query N°Matricule ou Nom
     * @param string $typeRec type de la recherche. TYPE_NAME: rechercher en utilisant le nom du (de la) canditat(e). TYPE_MATRICULE: rechercher en utilisant le N° Matricule du (de la) canditat(e).
     * @param string $exam Type de l'examen: EXAM_CEPE ou EXAM_BEPC
     * @return array
     * [
     *    "success": boolean,
     *    "data": null ou array [
     *        "nom": string,
     *        "matricule": string,
     *        "cisco": string,
     *        "ecole_origine": string,
     *        "observation": string, [
     *        "admis": boolean,
     *    ],
     *    "error_msg": null ou string
     * ]
     */
    private function get(string $query, string $typeRec, string $exam): array
    {
        $data = [
            "qs" => $query,
            "typeRc" => $typeRec,
            "typeexam" => $exam,
        ];
        $data = http_build_query($data);
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'Referer: https://www.education.gov.mg/gre-men/web/',
            'Origin: https://www.education.gov.mg',
            'Content-Length: ' . strlen($data),
            'Connection: keep-alive',
        ];
        $ctx = stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false
            ], /*Juste pour ignorer la verification des certificats SSL */
            'http' => [
                'method' => 'POST',
                'timeout' => 30,
                'user_agent' => 'Mozilla/5.0 (Linux; Android 8.1.0; V1818T) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/111.0.0.0 Mobile Safari/537.36',
                'header' => $headers,
                'content' => $data,
            ]
        ]);
        $url = "https://www.education.gov.mg/gre-men/web/";
        $data = file_get_contents($url, false, $ctx);

        $result = [
            'success' => false, //Initialement FALSE
        ];
        if (preg_match_all('#<tr class="([^"]*)">\s*<td[^>]*>([^<]+)</td>\s*<td[^>]*>([^<]+)</td>\s*<td[^>]*>([^<]+)</td>\s*<td[^>]*>([^<]+)</td>\s*<td[^>]*>([^<]+)</td>#is', $data, $matches, PREG_SET_ORDER)) {
            $result['success'] = true;
            foreach ($matches as $m) {
                $result['data'][] = [
                    'matricule' => $m[2],
                    'nom' => html_entity_decode($m[3]),
                    'cisco' => $m[4],
                    'ecole_origine' => $m[5],
                    'observation' => $m[6], //admis ou non admis
                    'admis' => ($m[1] == 'table-success'), //valeur boolean
                ];
            }
        } elseif (preg_match('#<td colspan="4" class="text-center text-danger">([^<]+)</td>\s*</tr>#is', $data, $m)) {
            $result["error_msg"] = $m[1];
        }

        return $result;
    }


    /**
     * Validation N° Matricule
     *
     * @param string $matricule N° Matricule
     * @return boolean
     */
    private function validerMatricule(string $matricule): bool
    {
        return preg_match(
            '#^(([\d]{3})+([0-9A-Z]{0,2})+([\d]{5})+-+([A-Z]?)+([\d]{2})+\/+([\d]{2})+([-]{0,1})+([\d]{0,2}))$#i',
            $matricule
        );
    }

}