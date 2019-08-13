<?php

namespace Sprint\Migration\Schema;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Loader;
use Bitrix\Main\SystemException;
use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;

class HlblockSchema extends AbstractSchema
{

    private $uniqs = [];

    protected function isBuilderEnabled()
    {
        return (Loader::includeModule('highloadblock'));
    }

    protected function initialize()
    {
        $this->setTitle('Схема highload-блоков');
    }

    public function getMap()
    {
        return ['hlblocks/'];
    }

    public function outDescription()
    {
        $schemas = $this->loadSchemas('hlblocks/', [
            'hlblock' => [],
            'fields' => [],
        ]);

        $cntFields = 0;
        foreach ($schemas as $schema) {
            $cntFields += count($schema['fields']);
        }

        $this->out('Highload-блоки: %d', count($schemas));
        $this->out('Полей: %d', $cntFields);
    }

    /**
     * @throws ArgumentException
     * @throws SystemException
     */
    public function export()
    {
        $helper = $this->getHelperManager();

        $exportItems = $helper->Hlblock()->exportHlblocks();

        foreach ($exportItems as $item) {
            $this->saveSchema('hlblocks/' . strtolower($item['NAME']), [
                'hlblock' => $item,
                'fields' => $helper->Hlblock()->exportFields($item['NAME']),
            ]);
        }
    }

    public function import()
    {
        $schemas = $this->loadSchemas('hlblocks/', [
            'hlblock' => [],
            'fields' => [],
        ]);

        foreach ($schemas as $schema) {
            $hlblockUid = $this->getUniqHlblock($schema['hlblock']);

            $this->addToQueue('saveHlblock', $schema['hlblock']);

            foreach ($schema['fields'] as $field) {
                $this->addToQueue('saveField', $hlblockUid, $field);
            }
        }

        foreach ($schemas as $schema) {
            $hlblockUid = $this->getUniqHlblock($schema['hlblock']);

            $skip = [];
            foreach ($schema['fields'] as $field) {
                $skip[] = $this->getUniqField($field);
            }

            $this->addToQueue('cleanFields', $hlblockUid, $skip);
        }

        $skip = [];
        foreach ($schemas as $schema) {
            $skip[] = $this->getUniqHlblock($schema['hlblock']);
        }

        $this->addToQueue('cleanHlblocks', $skip);
    }


    /**
     * @param $item
     * @throws ArgumentException
     * @throws SystemException
     * @throws HelperException
     */
    protected function saveHlblock($item)
    {
        $helper = $this->getHelperManager();
        $helper->Hlblock()->setTestMode($this->testMode);
        $helper->Hlblock()->saveHlblock($item);
    }

    /**
     * @param $hlblockUid
     * @param $field
     * @throws ArgumentException
     * @throws SystemException
     * @throws HelperException
     */
    protected function saveField($hlblockUid, $field)
    {
        $hlblockId = $this->getHlblockId($hlblockUid);
        if (!empty($hlblockId)) {
            $helper = $this->getHelperManager();
            $helper->Hlblock()->setTestMode($this->testMode);
            $helper->Hlblock()->saveField($hlblockId, $field);
        }
    }

    /**
     * @param array $skip
     * @throws ArgumentException
     * @throws SystemException
     * @throws HelperException
     */
    protected function cleanHlblocks($skip = [])
    {
        $helper = $this->getHelperManager();

        $olds = $helper->Hlblock()->getHlblocks();
        foreach ($olds as $old) {
            $uniq = $this->getUniqHlblock($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Hlblock()->deleteHlblock($old['ID']);
                $this->outWarningIf($ok, 'Highload-блок %s: удален', $old['NAME']);
            }
        }
    }

    /**
     * @param $hlblockUid
     * @param array $skip
     * @throws ArgumentException
     * @throws SystemException
     * @throws HelperException
     */
    protected function cleanFields($hlblockUid, $skip = [])
    {
        $hlblockId = $this->getHlblockId($hlblockUid);
        if (!empty($hlblockId)) {
            $helper = $this->getHelperManager();
            $olds = $helper->Hlblock()->getFields($hlblockId);
            foreach ($olds as $old) {
                $uniq = $this->getUniqField($old);
                if (!in_array($uniq, $skip)) {
                    $ok = ($this->testMode) ? true : $helper->Hlblock()->deleteField($hlblockId, $old['FIELD_NAME']);
                    $this->outWarningIf($ok, 'Поле highload-блока %s: удалено', $old['FIELD_NAME']);
                }
            }
        }
    }

    /**
     * @param $hlblockUid
     * @throws ArgumentException
     * @throws SystemException
     * @return mixed
     */
    protected function getHlblockId($hlblockUid)
    {
        $helper = $this->getHelperManager();

        if (isset($this->uniqs[$hlblockUid])) {
            return $this->uniqs[$hlblockUid];
        }

        list($tableName, $hlblockName) = explode(':', $hlblockUid);

        $this->uniqs[$hlblockUid] = $helper->Hlblock()->getHlblockId($hlblockName);
        return $this->uniqs[$hlblockUid];
    }

    protected function getUniqField($item)
    {
        return $item['FIELD_NAME'];
    }

    protected function getUniqHlblock($item)
    {
        return $item['TABLE_NAME'] . ':' . $item['NAME'];
    }


}