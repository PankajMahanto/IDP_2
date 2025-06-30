import sys
import json
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

def main():
    try:
        input_json = sys.argv[1]
        data = json.loads(input_json)
    except Exception as e:
        print(json.dumps({"error": f"Invalid Input: {str(e)}"}))
        sys.exit(1)

    seeker_profile = data.get('seeker_profile', '')
    jobs_data = data.get('jobs', [])

    if not seeker_profile or not jobs_data:
        print(json.dumps({"error": "Missing data"}))
        sys.exit(1)

    # Prepare text corpus
    corpus = [seeker_profile] + [job['text'] for job in jobs_data]

    # TF-IDF Vectorization
    vectorizer = TfidfVectorizer(stop_words='english')
    tfidf_matrix = vectorizer.fit_transform(corpus)

    # Calculate cosine similarity (user profile vs jobs)
    seeker_vector = tfidf_matrix[0]
    job_vectors = tfidf_matrix[1:]

    similarities = cosine_similarity(seeker_vector, job_vectors).flatten()

    # Always pick top N jobs (even low similarity for testing)
    top_n = min(10, len(similarities))  # don't exceed available jobs
    top_indices = similarities.argsort()[-top_n:][::-1]  # highest similarity first

    recommended_jobs = []
    for idx in top_indices:
        recommended_jobs.append({
            'job_id': jobs_data[idx]['job_id'],
            'similarity': round(float(similarities[idx]), 4)
        })

    print(json.dumps({"recommended_jobs": recommended_jobs}))

if __name__ == "__main__":
    main()
