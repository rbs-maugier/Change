<?php
namespace Change\Workflow\Interfaces;

/**
* @name \Change\Workflow\Interfaces\Workflow
*/
interface Workflow
{
	/**
	 * Return Short name
	 * @return string
	 */
	public function getName();

	/**
	 * Return all Workflow items defined
	 * @return \Change\Workflow\Interfaces\WorkflowItem[]
	 */
	public function getItems();

	/**
	 * @param integer $id
	 * @return \Change\Workflow\Interfaces\WorkflowItem|null
	 */
	public function getItemById($id);

	/**
	 * @return boolean
	 */
	public function isValid();

	/**
	 * @return \DateTime|null
	 */
	public function getStartDate();

	/**
	 * @return \DateTime|null
	 */
	public function getEndDate();

	/**
	 * @return string
	 */
	public function startTask();

	/**
	 * @return string
	 */
	public function getErrors();

	/**
	 * @return \Change\Workflow\Interfaces\WorkflowInstance
	 */
	public function createWorkflowInstance();
}