# Makefile for creating Stylesheets
# http://www.thumbtack.com/engineering/makefiles-for-less-and-css/

# Just call make all
SOURCES=$(shell ls less/*.less)
SOURCES:=$(filter-out less/vufind.less, $(SOURCES))
TARGETS=$(patsubst less/%.less,css/%.css,$(SOURCES))
DEPEND=$(patsubst less/%.less,make/%.d,$(SOURCES))



# for debugging. usage: make print-VARIABLE
print-%:
	@echo '$*=$($*)'
clean:
	rm -f make/*.d
	rm -f css/*.css*
	make -j${ncpu} all
css/%.css: less/%.less
	lessc -s -M '$<' '$@' > 'make/$*.d'
	lessc --source-map --clean-css="--s0 --advanced" -s $< $@
-include $(DEPEND)
all: $(TARGETS)