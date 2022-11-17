import Job from '../../types/Job';

// @todo: Implement dom structure once https://helsinkisolutionoffice.atlassian.net/browse/UHF-7111 is done
const ResultCard = (props: Job) => {
  return <li>{JSON.stringify(props)}</li>;
};

export default ResultCard;
