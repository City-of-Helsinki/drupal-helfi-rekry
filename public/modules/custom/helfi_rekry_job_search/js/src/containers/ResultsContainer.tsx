import Pagination from '../components/results/Pagination';
import ResultCard from '../components/results/ResultCard';

type ResultsContainerProps = {
  hits?: {
    hits: any[];
    total: {
      value: number;
    };
  };
  isLoading: boolean;
  size: number;
};

const ResultsContainer = ({ hits, isLoading, size }: ResultsContainerProps) => {
  // @todo add no results message.
  if (!hits?.hits) {
    return <div>{Drupal.t('No results')}</div>;
  }

  const results = hits.hits;
  const total = hits.total.value;
  const pages = Math.floor(total / size);
  const addLastPage = total > size && total % size;

  return (
    <div>
      {isLoading && <div>loading...</div>}
      {results.map((hit: any) => (
        <ResultCard key={hit._id} {...hit._source} />
      ))}
      <Pagination pages={5} totalPages={addLastPage ? pages + 1 : pages} />
    </div>
  );
};

export default ResultsContainer;
