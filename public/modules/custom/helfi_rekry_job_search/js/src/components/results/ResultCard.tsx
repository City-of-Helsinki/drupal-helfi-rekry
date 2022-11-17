import { fromUnixTime } from 'date-fns';

import Job from '../../types/Job';

// @todo: Implement dom structure once https://helsinkisolutionoffice.atlassian.net/browse/UHF-7111 is done
const ResultCard = ({
  title,
  field_employment_type,
  field_job_duration,
  field_organization_name,
  field_publication_starts,
  unpublish_on,
}: Job) => {
  return (
    <li>
      <div>
        <h3>{title}</h3>
        <p>Employment type: {field_employment_type}</p>
        <p>Job duration: {field_job_duration}</p>
        <p>Organization name: {field_organization_name}</p>
        <p>Publication starts: {fromUnixTime(field_publication_starts).toLocaleString()}</p>
        <p>Unpublish on: {fromUnixTime(unpublish_on).toLocaleString()}</p>
      </div>
    </li>
  );
};

export default ResultCard;
