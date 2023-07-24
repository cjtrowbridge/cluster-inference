# cluster-inference
This version of cluster-inference is designed to locally replicate the OpenAI API from within a docker container using open source LLMs running GGML.

It costs nothing these days to aquire a used dual-Xeon rack server with a couple hundred gigs of ram. Tools like GGML allow us to run large language models on these machines without needing any graphics cards. Sure, it's slower, but we're not global conglomerates with high performance demands for immediate results from the model. We can afford to queue tasks up and let them run overnight.

The potential benefits of combining this approach with something like AutoGPT can not be overestimated.

##Setup

```
# Put your ggml models into the /var/ai directory on your docker server. I use BitTorrent Sync to get them there easily from my NAS.

# Clone this repository onto your docker server
git clone https://github.com/cjtrowbridge/cluster-inference

# Build the dockerfile
cd cluster-inference
make build

# Open the web ui to view your models and run inference.

```

##Coming Soon

I want to incorporate bittorrent support for model distribution which just seems so obvious to me.

##Keep In Mind

A lot of attention is being paid to extending AI models into edge cases, and all of this work benefits homelabs with a lot more CPU cores than GPU cores.

For best results, you want CPUs with AVX, AVX2, or AVX512 instruction sets. My favorite so far is the Poweredge R630 with the E5-2600 v4. This comes with24 Xeon cores and it's easy to find them with 192 gigs of ram or more for just a few hundred bucks. This will run any of the publicly available large langauge models with ease. I get 2-3 tokens/second running MPT-7B-Instruct on this hardware with no graphics card whatsoever.

There are also builds of tensorflow and GGML for non-avx CPUs (they're just going to run much slower).

