# Makefile for creating Stylesheets
# Do not modify in IDE - it replaces tabsw with spaces. 
# http://www.thumbtack.com/engineering/makefiles-for-less-and-css/

# Just call make all


SOURCES = $(shell ls less/*.less)
SOURCES := $(filter-out less/vufind.less, $(SOURCES))
TARGETS = $(patsubst less/%.less,css/%.css,$(SOURCES))
DEPEND = $(patsubst less/%.less,make/%.d,$(SOURCES))
print-%:
	@echo '$*=$($*)'
clean: 
	rm -f make/*.d
	rm -f map/*.map
	rm -f $(TARGETS)
	make all
css/%.css: less/%.less 
	lessc -s -M '$<' '$@' > 'make/$*.d'
	lessc -s $< --source-map-map-inline > $@ 
-include $(DEPEND)
all: $(TARGETS)

