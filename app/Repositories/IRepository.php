<?php

namespace App\Repositories;

interface IRepository
{
	/**
	 * Get all records from the repository.
	 *
	 * @return mixed
	 */
	public function all();

	/**
	 * Find a record by its ID.
	 *
	 * @param int $id
	 * @return mixed
	 */
	public function find($id);

	/**
	 * Create a new record in the repository.
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function create(array $data);

	/**
	 * Update a record in the repository.
	 *
	 * @param int $id
	 * @param array $data
	 * @return mixed
	 */
	public function update($id, array $data);

	/**
	 * Delete a record from the repository.
	 *
	 * @param int $id
	 * @return mixed
	 */
	public function delete($id);
}
