import { format, fromUnixTime } from 'date-fns';
import { HTMLAttributes } from 'react';

import Job from '../../types/Job';

// @todo: Implement dom structure once https://helsinkisolutionoffice.atlassian.net/browse/UHF-7111 is done
const ResultCard = ({
  title,
  field_copied,
  field_original_language,
  field_employment,
  field_employment_type,
  field_job_duration,
  field_jobs,
  field_organization_name,
  unpublish_on,
  url,
}: Job) => {
  if (!title || !title.length) {
    return null;
  }

  let heading = title[0];

  let jobs_amount = null;
  if (field_jobs[0] > 1) {
    jobs_amount = ` (${field_jobs} ${Drupal.t('jobs')})`;
  }

  const customAtts: HTMLAttributes<HTMLHeadingElement | HTMLDivElement> = {};
  if (field_copied?.length && field_original_language?.length) {
    customAtts.lang = field_original_language[0];
  }

  return (
    <div role='article' className='node--type-job-listing node--view-mode-teaser'>
      <h3 className='job-listing__title'>
        <a href={url[0]} rel='bookmark'>
          <span {...customAtts}>{heading.charAt(0).toUpperCase() + heading.slice(1)}</span>
          {jobs_amount && <span>{jobs_amount}</span>}
        </a>
      </h3>
      <section
        aria-label={Drupal.t(
          'Tags',
          {},
          {
            context:
              'Label for screen reader software users explaining that this is a list of tags related to this page.',
          }
        )}
        className='content-tags'
      >
        <ul className='content-tags__tags content-tags__tags--static'>
          {field_employment_type && (
            <li className='content-tags__tags__tag'>
              <span className='employment-type'>{field_employment_type}</span>
            </li>
          )}
          {field_employment && (
            <li className='content-tags__tags__tag'>
              <span className='employment'>{field_employment}</span>
            </li>
          )}
        </ul>
      </section>
      <div className='job-listing__organization-name' {...customAtts}>
        <span className='organization'>{field_organization_name && field_organization_name.length && field_organization_name[0]}</span>
      </div>
      <div className='job-listing__metadata job-listing__metadata--application-ends'>
        <span className='job-listing__metadata__label'>
          <span className='hel-icon hel-icon--clock ' aria-hidden='true'></span>
          {Drupal.t('Application period ends')}
        </span>
        <span className='job-listing__metadata__content'>
          {unpublish_on ? format(fromUnixTime(unpublish_on[0]), 'd.M.Y H:mm') : '-'}
        </span>
      </div>
      <div className='job-listing__metadata job-listing__metadata--job-duration'>
        <span className='job-listing__metadata__label'>
          <span className='hel-icon hel-icon--calendar ' aria-hidden='true'></span>
          {Drupal.t('Employment contract')}
        </span>
        <span className='job-listing__metadata__content' {...customAtts}>
          {field_job_duration || '-'}
        </span>
      </div>
      <span className='hel-icon hel-icon--arrow-right' aria-hidden='true'></span>
    </div>
  );
};

export default ResultCard;
