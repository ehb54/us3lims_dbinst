<?php
/*
 * Settings a global fit cannot be submitted with.
 */

/**
 * Why a 2DSA submission with these job parameters cannot run as a global fit,
 * or '' when it can. us_mpi_analysis refuses a meniscus fit in a global fit,
 * and a bottom fit in one writes two result models to one file name, so the
 * import fails after the job has run.
 *
 * @param mixed $job_parameters the payload's job_parameters
 * @param int   $num_datasets   datasets in the submission
 * @param int   $separate_datasets 0 for a global fit
 */
function global_fit_refusal( $job_parameters, $num_datasets, $separate_datasets )
{
  if ( $num_datasets < 2  ||  $separate_datasets != 0  ||  ! is_array( $job_parameters ) )
    return '';

  if ( (int)( $job_parameters['fit_mb_select'] ?? 0 ) > 0 )
    return 'A global fit cannot also fit the meniscus or bottom. ' .
           'Set Fit Meniscus | Bottom to None, or submit the datasets as separate jobs.';

  return '';
}
