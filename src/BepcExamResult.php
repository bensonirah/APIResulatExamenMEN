<?php

namespace Miavaka\MenExamResult;

final class BepcExamResult implements ExamResultInterface
{

    const TYPE_NAME = 'nom';
    const TYPE_MATRICULE = 'mle';
    const EXAM_BEPC = 'bepc';
    /**
     * @var DataProviderInterface
     */
    private $dataProvider;

    /**
     * @param DataProviderInterface $dataProvider
     */
    public function __construct(DataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function search(string $query): string
    {
        $typeRec = self::TYPE_NAME;
        if ($this->validerMatricule($query)) {
            $typeRec = self::TYPE_MATRICULE;
        }
        $result = json_decode(json_encode($this->dataProvider->get($query, $typeRec, self::EXAM_BEPC)));
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