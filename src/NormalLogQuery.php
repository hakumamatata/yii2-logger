<?php

namespace hakumamatata\yii2logger;

/**
 * This is the ActiveQuery class for [[NormalLog]].
 *
 * @see NormalLog
 */
class NormalLogQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * {@inheritdoc}
     * @return NormalLog[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * {@inheritdoc}
     * @return NormalLog|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
